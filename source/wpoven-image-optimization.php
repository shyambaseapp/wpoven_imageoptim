<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.wpoven.com
 * @since             1.0.0
 * @package           Wpoven_Image_Optimization
 *
 * @wordpress-plugin
 * Plugin Name:       WPOven Image Optimization
 * Plugin URI:        https://www.wpoven.com
 * Description:       Optimized images in webp formate to reduced page loading time & load page faster.
 * Version:           1.0.0
 * Author:            WPOven
 * Author URI:        https://www.wpoven.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpoven-image-optimization
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WPOVEN_IMAGE_OPTIMIZATION_VERSION', '1.0.0');
if (!defined('WPOVEN_IMAGE_OPTIMIZATION_SLUG'))
	define('WPOVEN_IMAGE_OPTIMIZATION_SLUG', 'wpoven-image-optimization');

define('WPOVEN_IMAGE_OPTIMIZATION', 'WPOven Image Optimization');
define('WPOVEN_IMAGE_OPTIMIZATION_ROOT_PL', __FILE__);
define('WPOVEN_IMAGE_OPTIMIZATION_ROOT_URL', plugins_url('', WPOVEN_IMAGE_OPTIMIZATION_ROOT_PL));
define('WPOVEN_IMAGE_OPTIMIZATION_ROOT_DIR', dirname(WPOVEN_IMAGE_OPTIMIZATION_ROOT_PL));
define('WPOVEN_IMAGE_OPTIMIZATION_PLUGIN_DIR', plugin_dir_path(__DIR__));
define('WPOVEN_IMAGE_OPTIMIZATION_PLUGIN_BASE', plugin_basename(WPOVEN_IMAGE_OPTIMIZATION_ROOT_PL));
define('WPOVEN_IMAGE_OPTIMIZATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPOVEN_FILE',           __FILE__);
define('WPOVEN_OPTIMIZATION_PATH',           realpath(plugin_dir_path(WPOVEN_FILE)) . '/');


require_once plugin_dir_path(__FILE__) . 'includes/libraries/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/baseapp/wpoven_imageoptim/',
	__FILE__,
	'wpoven-image-optimization'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpoven-image-optimization-activator.php
 */

include_once(WPOVEN_OPTIMIZATION_PATH . 'classes/Webp/ServerConfigGenerator.php');

function activate_wpoven_image_optimization()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wpoven-image-optimization-activator.php';
	Wpoven_Image_Optimization_Activator::activate();
	$serverConfigGenerator = new ServerConfigGenerator();
	$serverConfigGenerator->generateServerConfig();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpoven-image-optimization-deactivator.php
 */
function deactivate_wpoven_image_optimization()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wpoven-image-optimization-deactivator.php';
	Wpoven_Image_Optimization_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wpoven_image_optimization');
register_deactivation_hook(__FILE__, 'deactivate_wpoven_image_optimization');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wpoven-image-optimization.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpoven_image_optimization()
{
	$plugin = new Wpoven_Image_Optimization();
	$plugin->run();
}
run_wpoven_image_optimization();


function wpoven_image_optimization_plugin_settings_link($links)
{
	$settings_link = '<a href="' . admin_url('admin.php?page=' . WPOVEN_IMAGE_OPTIMIZATION_SLUG) . '">Settings</a>';

	array_push($links, $settings_link);
	return $links;
}
add_filter('plugin_action_links_' . WPOVEN_IMAGE_OPTIMIZATION_PLUGIN_BASE, 'wpoven_image_optimization_plugin_settings_link');
