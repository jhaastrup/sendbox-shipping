<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              #
 * @since             1.0.0
 * @package           Wooss
 *
 * @wordpress-plugin
 * Plugin Name:       Sendbox Shipping
 * Plugin URI:        #
 * Description:       This is a woocommerce plugin that enables you ship from your store in Nigeria to anywhere in the world.
 * Version:           3.2.1
 * Author:            sendbox
 * Author URI:        https://sendbox.ng/ 
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wooss
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WOOSS_VERSION', '3.2.2');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wooss-activator.php
 */
function activate_wooss()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wooss-activator.php';
	Wooss_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wooss-deactivator.php
 */
function deactivate_wooss()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wooss-deactivator.php';
	Wooss_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wooss');
register_deactivation_hook(__FILE__, 'deactivate_wooss');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wooss.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wooss()
{
	$plugin = new WooSS();
	$plugin->run();
}
run_wooss();