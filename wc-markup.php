<?php

/**
 * @link              https://wpiron.com
 * @since             1.1.7
 * @package           markup_for_woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Markup For Woocommerce
 * Plugin URI:        https://wpiron.com
 * Description:       Create and apply your own markups on products and variations. Easily add a markup to specific product types, categories, attributes. Native bulk actions is available.
 * Version:           1.8.6
 * Author:            WP Iron
 * Author URI:        https://wpiron.com
 * Text Domain:       wc-markup
 * Domain Path:       /languages
 * Requires Plugins: woocommerce
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}
/**
 * Currently plugin version.
 * Start at version 1.1.1 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WC_MARKUP_VERSION', '1.8.6' );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-markup-activator.php
 */
function WCMRKP_activate_wc_markup()
{
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-markup-activator.php';
		Wc_Markup_Activator::activate();
	} else{
		deactivate_plugins(plugin_basename(__FILE__));
		wp_die('Markup for WooCommerce requires WooCommerce to be installed and active. <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
	}
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-markup-deactivator.php
 */
function WCMRKP_deactivate_wc_markup()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-markup-deactivator.php';
	Wc_Markup_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'WCMRKP_activate_wc_markup' );
register_deactivation_hook( __FILE__, 'WCMRKP_deactivate_wc_markup' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-markup.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.1.1
 */
function run_wc_markup()
{
	$plugin = new Wc_Markup();
	$plugin->run();
}


run_wc_markup();