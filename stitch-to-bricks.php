<?php
/**
 * Plugin Name:       Stitch to Bricks Import Assistant
 * Description:       Fetches Google Stitch screens and hands them off to Bricks native HTML & CSS import.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Antigravity
 * Text Domain:       stitch-to-bricks
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('STB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('STB_PLUGIN_VERSION', '1.0.0');

/**
 * Main plugin class.
 */
final class Stitch_To_Bricks
{

	/**
	 * Instance of this class.
	 */
	private static $instance = null;

	/**
	 * Returns the instance of this class.
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct()
	{
		// Wait for Bricks to be loaded before initializing our builder components
		add_action('plugins_loaded', [$this, 'init']);
	}

	/**
	 * Initialize the plugin.
	 */
	public function init()
	{
		// Always load settings page so API key can be entered
		$this->includes();
		$this->init_classes();
	}

	/**
	 * Include necessary classes.
	 */
	private function includes()
	{
		require_once STB_PLUGIN_DIR . 'includes/class-stb-settings.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-builder-ui.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-stitch-client.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-ajax-handler.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-ai-client.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-design-manager.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-ai-designer-ui.php';
	}

	/**
	 * Initialize the required classes.
	 */
	private function init_classes()
	{
		new STB_Settings();
		new STB_Builder_UI();
		new STB_Ajax_Handler();
		new STB_Design_Manager();
		new STB_AI_Designer_UI();
	}
}

/**
 * Helper function for logging debug messages.
 * Logs to wp-content/debug.log if WP_DEBUG_LOG is true.
 */
function stb_log($message)
{
	if (WP_DEBUG === true) {
		if (is_array($message) || is_object($message)) {
			error_log(print_r($message, true));
		} else {
			error_log($message);
		}
	}
}

/**
 * Run the plugin.
 */
function run_stitch_to_bricks()
{
	Stitch_To_Bricks::get_instance();
}

run_stitch_to_bricks();
