<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class Disciple Tools - Oikos Interchange_Settings_Tile
 *
 * This class will add navigation and a custom section to the Settings page in Disciple.Tools.
 * The dt_profile_settings_page_menu function adds a navigation link to the bottom of the nav section in Settings.
 * The dt_profile_settings_page_sections function adds a custom content tile to the bottom of the page.
 *
 * It is likely modifications through this section will leverage a custom REST end point to process changes.
 * @see /rest-api/ in this plugin for a custom REST endpoint
 */

class Disciple_Tools_Oikos_Interchange_Settings_Tile
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        if ( 'settings' === dt_get_url_path() ) {
            add_action( 'dt_profile_settings_page_menu', [ $this, 'dt_profile_settings_page_menu' ], 100, 4 );
            add_action( 'dt_profile_settings_page_sections', [ $this, 'dt_profile_settings_page_sections' ], 100, 4 );
            add_action( 'dt_modal_help_text', [ $this, 'dt_modal_help_text' ], 100 );
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'wp-api' );
        wp_localize_script( 'wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw( rest_url() ),
            'nonce' => wp_create_nonce( 'wp_rest' )
        ));
    }

    /**
     * Adds menu item
     *
     * @param $dt_user WP_User object
     * @param $dt_user_meta array Full array of user meta data
     * @param $dt_user_contact_id bool/int returns either id for contact connected to user or false
     * @param $contact_fields array Array of fields on the contact record
     */
    public function dt_profile_settings_page_menu( $dt_user, $dt_user_meta, $dt_user_contact_id, $contact_fields ) {
        ?>
        <li><a href="#dt_oikos_system_settings_id"><?php esc_html_e( 'Oikos Interchange System', 'dt-oikos-system' )?></a></li>
        <?php
    }

    /**
     * Adds custom tile
     *
     * @param $dt_user WP_User object
     * @param $dt_user_meta array Full array of user meta data
     * @param $dt_user_contact_id bool/int returns either id for contact connected to user or false
     * @param $contact_fields array Array of fields on the contact record
     */
    public function dt_profile_settings_page_sections( $dt_user, $dt_user_meta, $dt_user_contact_id, $contact_fields ) {
        ?>
        <div class="cell bordered-box" id="dt_oikos_system_settings_id" data-magellan-target="dt_oikos_system_settings_id">
            <button class="help-button float-right" data-section="dt-oikos-system-help-text">
                <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
            </button>
            <span class="section-header"><?php esc_html_e( 'Oikos Interchange System', 'dt-oikos-system' )?></span>
            <hr/>

            <!-- replace with your custom content -->
            <p>Oikos Interchange System connects your Disciple.Tools system to other Oikos Systems.</p>
            <input type="text" id="dt-oikos-system-adm-level" placeholder="Admin Level" value="0">
            <input type="text" id="dt-oikos-system-lng" placeholder="Longitude" value="10.4049">
            <input type="text" id="dt-oikos-system-lat" placeholder="Latitude" value="35.918">
            <button class="button" id="dt-oikos-system-connect-button">Connect to Oikos System</button>
            <script>
                jQuery(document).ready(function($) {
                    $('#dt-oikos-system-connect-button').on('click', function() {
                        // Get values from input fields
                        const params = {
                            adm_level: $('#dt-oikos-system-adm-level').val() || null,
                            lng: $('#dt-oikos-system-lng').val() || null,
                            lat: $('#dt-oikos-system-lat').val() || null
                        };
                        
                        // Call the REST API
                        jQuery.ajax({
                            url: window.wpApiSettings.root + 'dt-oikos-system/v1/phase',
                            method: 'POST',
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('X-WP-Nonce', window.wpApiSettings.nonce);
                            },
                            data: params,
                            success: function(response) {
                                console.log('Oikos System API Response:', response);
                            },
                            error: function(xhr, status, error) {
                                console.error('Oikos System API Error:', error);
                                console.log('Response:', xhr.responseText);
                            }
                        });
                    });
                });
            </script>

        </div>
        <?php
    }

    /**
     * @see disciple-tools-theme/dt-assets/parts/modals/modal-help.php
     */
    public function dt_modal_help_text(){
        ?>
        <div class="help-section" id="dt-oikos-system-help-text" style="display: none">
            <h3><?php echo esc_html_x( 'Custom Settings Section', 'Optional Documentation', 'dt-oikos-system' ) ?></h3>
            <p><?php echo esc_html_x( 'Add your own help information into this modal.', 'Optional Documentation', 'dt-oikos-system' ) ?></p>
        </div>
        <?php
    }
}

Disciple_Tools_Oikos_Interchange_Settings_Tile::instance();
