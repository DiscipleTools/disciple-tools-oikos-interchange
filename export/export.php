<?php
/**
 * Class DT_Oikos_Export
 * Adds export functionality to the list page
 */
class DT_Oikos_Export {
    
    public function __construct() {
        add_action( 'dt_list_exports_menu_items', [ $this, 'add_export_menu_items' ], 10, 1 );
        add_filter( 'dt_post_list_exports_filters_sidebar_help_text', [ $this, 'add_export_help_text' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script( 'wp-api' );
        wp_localize_script( 'wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw( rest_url() ),
            'nonce' => wp_create_nonce( 'wp_rest' )
        ));
    }
    
    /**
     * Add export menu items to the list exports section
     *
     * @param string $post_type The post type being displayed
     */
    public function add_export_menu_items( $post_type ) {
        if ( $post_type === 'contacts' || $post_type === 'groups' ) {
            ?>
            <a id="export_oikos_data"><?php esc_html_e( 'Oikos Interchange Export', 'disciple-tools-oikos-interchange' ) ?></a><br>
            <script>
                jQuery(document).ready(function($) {
                    $('#export_oikos_data').on('click', function() {
                        // Get the current filter from URL query parameters
                        const urlParams = new URLSearchParams(window.location.search);
                        const queryParam = urlParams.get('query');
                        let filter = {};
                        
                        if (queryParam) {
                            try {
                                // First decode the base64 string
                                const decodedQuery = atob(queryParam);
                                // Then parse the JSON
                                filter = JSON.parse(decodedQuery);
                            } catch (e) {
                                console.error('Error parsing filter:', e);
                            }
                        }
                        console.log('Filter:', filter);
                        // Call the REST API to get phases data with the current filter
                        jQuery.ajax({
                            url: window.wpApiSettings.root + 'dt-oikos-system/v1/phase',
                            method: 'POST',
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('X-WP-Nonce', window.wpApiSettings.nonce);
                            },
                            data: {
                                filter: filter,
                                post_type: urlParams.get('post_type') || window.detailsSettings?.post_type || '<?php echo esc_js( $post_type ); ?>'
                            },
                            success: function(response) {
                                // Create a JSON blob and trigger download
                                const data = JSON.stringify(response, null, 2);
                                const blob = new Blob([data], {type: 'application/json'});
                                const url = window.URL.createObjectURL(blob);
                                
                                // Create a temporary link and trigger download
                                const a = document.createElement('a');
                                a.style.display = 'none';
                                a.href = url;
                                a.download = 'oikos_export_' + new Date().toISOString().slice(0, 10) + '.json';
                                document.body.appendChild(a);
                                a.click();
                                
                                // Clean up
                                window.URL.revokeObjectURL(url);
                                document.body.removeChild(a);
                            },
                            error: function(xhr, status, error) {
                                console.error('Oikos Export Error:', error);
                                console.log('Response:', xhr.responseText);
                                alert('Error exporting Oikos data. See console for details.');
                            }
                        });

                    });
                });
            </script>
            <?php
        }
    }
    
    /**
     * Add help text for the export option
     *
     * @param array $help_text The existing help text array
     * @return array Modified help text array
     */
    public function add_export_help_text( $help_text ) {
        $help_text[] = [
            'title' => __( 'Oikos Interchange Export', 'disciple-tools-oikos-interchange' ),
            'text' => __( 'Export data in a format compatible with the Oikos Interchange System.', 'disciple-tools-oikos-interchange' )
        ];
        
        return $help_text;
    }
    
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}

// Initialize the singleton instance
DT_Oikos_Export::instance();
