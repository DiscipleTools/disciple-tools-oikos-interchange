<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_Oikos_Queries
 * 
 * Static class for handling database queries related to Oikos System
 */
class DT_Oikos_Queries {
    
    /**
     * Query phases data from the database
     * 
     * @param array $args Optional. Arguments to filter the query results.
     * @return array Array of phases data
     */
    public static function phases_query( $args = [] ) {
        global $wpdb;
        $location_ids = [];
        
        // Extract and sanitize input parameters
        $adm_level = isset($args['adm_level']) ? intval($args['adm_level']) : 0;
        $lng = isset($args['lng']) ? floatval($args['lng']) : 0;
        $lat = isset($args['lat']) ? floatval($args['lat']) : 0;
        
        // Get grid_id from coordinates if provided
        if ($lng && $lat) {
            $grid_id = self::get_grid_id_from_coordinates($lng, $lat, $adm_level);
            
            // Get location IDs for the query
            if (isset($grid_id)) {
                $location_ids = self::get_location_ids_for_grid($grid_id);
            }
        }
        
        // If we don't have location IDs, we can't proceed with meaningful queries
        if (empty($location_ids)) {
            return 0;
        }
        
        $location_ids_str = implode(',', array_map('intval', $location_ids));
        
        // Check for phase 5: Churches with generations
        $churches_results = self::query_churches($wpdb, $location_ids_str);
        
        if (!empty($churches_results)) {
            $church_ids = array_column($churches_results, 'post_id');
            
            if (!empty($church_ids)) {
                $church_generations_results = self::query_church_generations($wpdb, $church_ids, $location_ids_str);
                
                if (!empty($church_generations_results)) {
                    return 5; // Phase 5: Churches with generations
                }
                
                return 4; // Phase 4: Churches without generations
            }
        }
        
        // Check for phase 3: Baptized believers
        $baptisms_results = self::query_baptisms($wpdb, $location_ids_str);
        
        if (!empty($baptisms_results)) {
            return 3; // Phase 3: Baptized believers
        }
        
        // Check for phase 2: Contacts (gospel sharing)
        $contacts_results = self::query_contacts($wpdb, $location_ids_str);
        
        if (!empty($contacts_results)) {
            return 2; // Phase 2: Gospel sharing
        }
        
        return 0; // Phase 0: No activity
    }
    
    /**
     * Get grid ID from coordinates using Location_Grid_Geocoder
     * 
     * @param float $lng Longitude
     * @param float $lat Latitude
     * @param int $adm_level Admin level
     * @return int|null Grid ID if found, null otherwise
     */
    private static function get_grid_id_from_coordinates($lng, $lat, $adm_level) {
        // Make sure the Location_Grid_Geocoder class is available
        if (!class_exists('Location_Grid_Geocoder')) {
            require_once(get_template_directory() . '/dt-mapping/geocode-api/location-grid-geocoder.php');
        }
        
        // Initialize the geocoder
        $geocoder = new Location_Grid_Geocoder();
        
        // Get the geocoded data
        $geocoded_data = $geocoder->get_grid_id_by_lnglat($lng, $lat, $adm_level);
        
        // Extract relevant data if geocoding was successful
        if ($geocoded_data && !empty($geocoded_data['grid_id'])) {
            return $geocoded_data['grid_id'];
        }
        
        return null;
    }
    
    /**
     * Get location IDs for a given grid ID
     * 
     * @param int $grid_id Grid ID
     * @return array Array of location IDs
     */
    private static function get_location_ids_for_grid($grid_id) {
        $location_ids = [];
        
        // Check if Disciple_Tools_Mapping_Queries class is available
        if (!class_exists('Disciple_Tools_Mapping_Queries')) {
            require_once(get_template_directory() . '/dt-mapping/mapping-queries.php');
        }
        
        // Get all children locations based on the grid_id
        $children_ids = Disciple_Tools_Mapping_Queries::get_children_by_grid_id($grid_id);
        
        // If we have children, collect their IDs
        if (!empty($children_ids)) {
            foreach ($children_ids as $child) {
                $location_ids[] = $child['id'];
            }
        }
        
        return $location_ids;
    }
    
    /**
     * Query for churches (Phase 4)
     * 
     * @param wpdb $wpdb WordPress database object
     * @param string $location_ids_str Comma-separated location IDs
     * @return array Query results
     */
    private static function query_churches($wpdb, $location_ids_str) {
        $churches_query = "
            SELECT 
                p.ID as post_id,
                lgm.grid_id,
                lg.latitude,
                lg.longitude
            FROM $wpdb->posts p
            INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
            INNER JOIN $wpdb->dt_location_grid_meta lgm ON p.ID = lgm.post_id
            INNER JOIN $wpdb->dt_location_grid lg ON lgm.grid_id = lg.grid_id
            WHERE p.post_type = 'groups'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'group_type'
            AND pm.meta_value = 'church'
            AND lgm.grid_id IN ($location_ids_str)
        ";
        
        $results = $wpdb->get_results($churches_query, ARRAY_A);
        dt_write_log('Churches query results:');
        dt_write_log($results);
        
        return $results;
    }
    
    /**
     * Query for church generations (Phase 5)
     * 
     * @param wpdb $wpdb WordPress database object
     * @param array $church_ids Array of church post IDs
     * @param string $location_ids_str Comma-separated location IDs
     * @return array Query results
     */
    private static function query_church_generations($wpdb, $church_ids, $location_ids_str) {
        $church_ids_str = implode(',', $church_ids);
        
        $church_generations_query = "
            SELECT 
                5 as phase,
                COUNT(DISTINCT p.ID) as total_gen_churches,
                lgm.grid_id,
                lg.latitude,
                lg.longitude
            FROM $wpdb->posts p
            INNER JOIN $wpdb->p2p p2p ON (p.ID = p2p.p2p_to OR p.ID = p2p.p2p_from)
            INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
            INNER JOIN $wpdb->dt_location_grid_meta lgm ON p.ID = lgm.post_id
            INNER JOIN $wpdb->dt_location_grid lg ON lgm.grid_id = lg.grid_id
            WHERE p.post_type = 'groups'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'group_type'
            AND pm.meta_value = 'church'
            AND (
                (p2p.p2p_from IN ($church_ids_str) AND p.ID = p2p.p2p_to)
                OR 
                (p2p.p2p_to IN ($church_ids_str) AND p.ID = p2p.p2p_from)
            )
            AND lgm.grid_id IN ($location_ids_str)
            GROUP BY lgm.grid_id
        ";
        
        $results = $wpdb->get_results($church_generations_query, ARRAY_A);
        dt_write_log('Church generations query results:');
        dt_write_log($results);
        
        return $results;
    }
    
    /**
     * Query for baptized believers (Phase 3)
     * 
     * @param wpdb $wpdb WordPress database object
     * @param string $location_ids_str Comma-separated location IDs
     * @return array Query results
     */
    private static function query_baptisms($wpdb, $location_ids_str) {
        $baptisms_query = "
            SELECT 
                3 as phase,
                COUNT(p.ID) as total_baptized,
                lgm.grid_id,
                lg.latitude,
                lg.longitude
            FROM $wpdb->posts p
            INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
            INNER JOIN $wpdb->dt_location_grid_meta lgm ON p.ID = lgm.post_id
            INNER JOIN $wpdb->dt_location_grid lg ON lgm.grid_id = lg.grid_id
            WHERE p.post_type = 'contacts'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'baptized'
            AND pm.meta_value = '1'
            AND lgm.grid_id IN ($location_ids_str)
            GROUP BY lgm.grid_id
        ";
        
        $results = $wpdb->get_results($baptisms_query, ARRAY_A);
        dt_write_log('Baptisms query results:');
        dt_write_log($results);
        
        return $results;
    }
    
    /**
     * Query for contacts/gospel sharing (Phase 2)
     * 
     * @param wpdb $wpdb WordPress database object
     * @param string $location_ids_str Comma-separated location IDs
     * @return array Query results
     */
    private static function query_contacts($wpdb, $location_ids_str) {
        $contacts_query = "
            SELECT 
                2 as phase,
                COUNT(p.ID) as total_contacts,
                lgm.grid_id,
                lg.latitude,
                lg.longitude
            FROM $wpdb->posts p
            INNER JOIN $wpdb->dt_location_grid_meta lgm ON p.ID = lgm.post_id
            INNER JOIN $wpdb->dt_location_grid lg ON lgm.grid_id = lg.grid_id
            WHERE p.post_type = 'contacts'
            AND p.post_status = 'publish'
            AND lgm.grid_id IN ($location_ids_str)
            GROUP BY lgm.grid_id
        ";
        
        $results = $wpdb->get_results($contacts_query, ARRAY_A);
        dt_write_log('Contacts query results:');
        dt_write_log($results);
        
        return $results;
    }
}
