<?php
/**
 * Plugin Name:       EME VZW Helper
 * Plugin URI:        https://github.com/liedekef/eme-vzw-helper
 * Description:       VZW compliance checklist, wettelijke publicaties (Formulier 1 & 2), statutenwijziging en UBO-beheer als uitbreiding op Events Made Easy.
 * Version:           1.0.2
 * Author:            Your Name
 * Author URI:        https://www.e-dynamics.be
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eme-vzw-helper
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Requires Plugins:  events-made-easy
 */

defined( 'ABSPATH' ) || exit;

define( 'EME_VZW_VERSION', '1.0.2' );
define( 'EME_VZW_FILE',    __FILE__ );
define( 'EME_VZW_DIR',     plugin_dir_path( __FILE__ ) );
define( 'EME_VZW_URL',     plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class ): void {
    $prefix = 'EMEVzw\\';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) return;
    $file = EME_VZW_DIR . 'includes/' . str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, strlen( $prefix ) ) ) . '.php';
    if ( file_exists( $file ) ) require_once $file;
} );

function eme_vzw_helper_init(): void {
    if ( ! function_exists( 'eme_get_event' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'EME VZW Helper vereist de plugin Events Made Easy.', 'eme-vzw-helper' )
                . '</p></div>';
        } );
        return;
    }
    EMEVzw\Admin\Menu::init();
    EMEVzw\Config::init();
    EMEVzw\Checklist::init();
    EMEVzw\Publications::init();
    EMEVzw\Notifications::init();
    EMEVzw\Assets::init();
}
add_action( 'plugins_loaded', 'eme_vzw_helper_init' );

register_activation_hook( __FILE__, [ 'EMEVzw\\Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'EMEVzw\\Installer', 'deactivate' ] );
register_uninstall_hook( __FILE__, [ 'EMEVzw\\Installer', 'uninstall' ] );
