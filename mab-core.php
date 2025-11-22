<?php
/**
 * Plugin Name:       MaB Core
 * Plugin URI:        https://example.com/mab-core (add your site if needed)
 * Description:       One core plugin for all MaB functionalities. 
 * Version:           1.0.1
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            MaB Legends
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mab-core
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'MAB_CORE_VERSION', '1.0.1' );
define( 'MAB_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAB_CORE_URL', plugin_dir_url( __FILE__ ) );

// Load dependencies
require_once MAB_CORE_PATH . 'includes/class-mab-activator.php';
require_once MAB_CORE_PATH . 'includes/class-mab-deactivator.php';
require_once MAB_CORE_PATH . 'includes/class-mab-link-checker.php';
require_once MAB_CORE_PATH . 'includes/class-mab-helpers.php';

require_once MAB_CORE_PATH . 'admin/class-mab-admin.php';
require_once MAB_CORE_PATH . 'public/class-mab-public.php';
require_once MAB_CORE_PATH . 'public/shortcodes/class-mab-shortcode-download.php';

// Activation/Deactivation hooks
register_activation_hook( __FILE__, [ 'MaB_Core_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'MaB_Core_Deactivator', 'deactivate' ] );

// Initialize
function mab_core_init() {
    load_plugin_textdomain( 'mab-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    new MaB_Core_Admin();
    new MaB_Core_Public();
    new MaB_Shortcode_Download();
}
add_action( 'plugins_loaded', 'mab_core_init' );