<?php
/**
 * Plugin Name:       Stitch to Bricks Translation Layer
 * Description:       Integrates Google Stitch AI UI designs directly into Bricks Builder.
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
		require_once STB_PLUGIN_DIR . 'includes/class-stb-ajax-handler.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-stitch-api.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-converter.php';
		require_once STB_PLUGIN_DIR . 'includes/class-stb-parser.php';

		if (defined('WP_CLI') && WP_CLI) {
			require_once STB_PLUGIN_DIR . 'includes/class-stb-cli.php';
		}
	}

	/**
	 * Initialize the required classes.
	 */
	private function init_classes()
	{
		new STB_Settings();
		new STB_Builder_UI();
		new STB_Ajax_Handler();

		// Enqueue Tailwind CDN onto the frontend and Builder iframe 
		// so that imported Stitch components render perfectly out of the box.
		add_action('wp_head', [$this, 'inject_tailwind_cdn'], 5);
	}

	/**
	 * Injects the Tailwind Play CDN into the head of the page.
	 * This ensures that Bricks Builder and the frontend immediately 
	 * render the utility classes without needing a local 3.5MB stylesheet.
	 */
	public function inject_tailwind_cdn()
	{
		echo '<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>';
		echo '<script>tailwind.config = { darkMode: "class" };</script>';
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
