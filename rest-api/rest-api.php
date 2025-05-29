<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Oikos_Interchange_Endpoints
{
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];

    public function add_api_routes() {
        $namespace = 'dt-oikos-system/v1';

        register_rest_route(
            $namespace, '/phase', [
                'methods'  => 'POST',
                'callback' => [ $this, 'get_phases' ],
                'permission_callback' => function( WP_REST_Request $request ) {
                    return $this->has_permission();
                },
            ]
        );
    }


    public function get_phases( WP_REST_Request $request ) {
        // Unpack request parameters
        $params = $request->get_params();
        
        dt_write_log('Request parameters:');
        dt_write_log($params);
        
        // Check if required parameters exist
        if (!isset($params['filter'])) {
            return new WP_Error('missing_filter', 'Filter parameter is required', ['status' => 400]);
        }
        
        if (!isset($params['post_type'])) {
            return new WP_Error('missing_post_type', 'Post type parameter is required', ['status' => 400]);
        }
        
        $filter = $params['filter'];
        $args = [];
        
        // Extract coordinates from filter if available
        if (isset($filter['location_grid'])) {
            // Get the first location from the filter
            $location = $filter['location_grid'][0] ?? null;
            if ($location) {
                $args['lng'] = $location['lng'] ?? 0;
                $args['lat'] = $location['lat'] ?? 0;
                $args['admin_level'] = $location['level'] ?? 0;
            }
        }
        
        // Get the post type from the filter or default to 'contacts'
        $post_type = $params['post_type'];

        $phases = [];
        
        // Get the list data using DT_Posts::list_posts
        $list_data = DT_Posts::list_posts($post_type, $filter);

        // Query the phase data using DT_Oikos_Queries
        $phase = 0;
        if (!empty($args['lng']) && !empty($args['lat']) && isset($args['admin_level'])) {
            $phase = DT_Oikos_Queries::phases_query($args);
        }
        
        // Loop through list_data to extract location information
        $phases = [];
        if (!empty($list_data['posts'])) {
            foreach ($list_data['posts'] as $post) {
                // Check if the post has location_grid_meta
                if (!empty($post['location_grid_meta'])) {
                    // Get the first location from the meta
                    $location = $post['location_grid_meta'][0] ?? null;
                    
                    if ($location) {
                        if (!isset($phases[$location['grid_id']])) {
                            $phases[$location['grid_id']] = [];
                        };
                        $phases[$location['grid_id']][] = [
                            'phase' => 0,
                            'area' => [
                                'adm_level' =>  $this->convert_admin_level_to_int( $location['level'] ?? 0 ),
                                'center' => [
                                    'lat' => $location['lat'] ?? 0,
                                    'lng' => $location['lng'] ?? 0
                                ]
                                ]
                        ];
                    }
                }
            }
        }
    
        return $phases ?: [];
    }

    /**
     * Convert admin level string to integer value
     * 
     * @param string $admin_level Admin level string (admin1, admin2, admin3, admin4, admin5)
     * @return int Integer representation of admin level (1-5), defaults to 0 if invalid
     */
    public static function convert_admin_level_to_int($admin_level) {
        if (empty($admin_level) || !is_string($admin_level)) {
            return 0;
        }
        
        // Remove any spaces and convert to lowercase for consistent matching
        $admin_level = strtolower(trim($admin_level));
        
        switch ($admin_level) {
            case 'admin1':
                return 1;
            case 'admin2':
                return 2;
            case 'admin3':
                return 3;
            case 'admin4':
                return 4;
            case 'admin5':
                return 5;
            default:
                return 0;
        }
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }
    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }
}
Disciple_Tools_Oikos_Interchange_Endpoints::instance();
