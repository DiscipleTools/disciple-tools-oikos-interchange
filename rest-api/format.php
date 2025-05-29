<?php 
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_Oikos_Format
 * 
 * Static class for formatting data for the Oikos Interchange API
 */
class DT_Oikos_Format {
    
    /**
     * Format phases data to PEP1 response format
     * 
     * @param array $phase Raw data from database query
     * @return array Formatted data in PEP1 response format
     */
    public static function format_phases_to_pep1( $args, $phase ) {
        $records = [];
        
        $record = [
            'phase' => isset( $phase ) ? intval( $phase ) : 0,
            'area' => [
                'adm_level' => isset( $args['admin_level'] ) ? intval( $args['admin_level'] ) : 0,
                'center' => [
                    'lat' => isset( $args['lat'] ) ? floatval( $args['lat'] ) : 0,
                    'lng' => isset( $args['lng'] ) ? floatval( $args['lng'] ) : 0,
                ]
            ]
        ];
        
        return $record;
    }
    
   
}
