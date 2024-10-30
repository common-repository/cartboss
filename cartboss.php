<?php

 /**
 * Plugin Name:       SMS Abandoned Cart Recovery - CartBoss
 * Plugin URI:        https://www.cartboss.io
 * Description:       Enhance your WooCommerce store's revenue by recovering abandoned carts with automated SMS messages. Offer discounts and special deals directly through text messaging, seamlessly integrated with your WordPress site.
 * Version:           4.1.0
 * Author:            Cart DATA Ltd.
 * Author URI:        https://www.cartboss.io
 * Text Domain:       cartboss
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to:      6.6.1
 * Requires PHP:      7.2
 * WC requires at least: 3.0.0
 * WC tested up to:   9.2.2
 */

 add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// polyfill
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

define('CARTBOSS_VERSION', '4.1.0');
define('CARTBOSS_PLUGIN_NAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cartboss-activator.php
 */
function activate_cartboss() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-cartboss-activator.php';
    Cartboss_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cartboss-deactivator.php
 */
function deactivate_cartboss() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-cartboss-deactivator.php';
    Cartboss_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_cartboss');
register_deactivation_hook(__FILE__, 'deactivate_cartboss');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-cartboss.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cartboss() {

    $plugin = new Cartboss();
    $plugin->run();

}

run_cartboss();
