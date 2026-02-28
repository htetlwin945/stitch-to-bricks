<?php
/**
 * Plugin Settings Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STB_Settings {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'page_init' ] );
	}

	public function add_plugin_page() {
		add_options_page(
			'Stitch to Bricks Settings', 
			'Stitch to Bricks', 
			'manage_options', 
			'stitch-to-bricks', 
			[ $this, 'create_admin_page' ]
		);
	}

	public function create_admin_page() {
		?>
		<div class="wrap">
			<h1>Stitch to Bricks Integration</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'stb_option_group' );
				do_settings_sections( 'stitch-to-bricks' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function page_init() {
		register_setting(
			'stb_option_group', // Option group
			'stb_stitch_api_key', // Option name
			[ $this, 'sanitize' ] // Sanitize
		);

		add_settings_section(
			'stb_setting_section_id', // ID
			'Google Stitch API Settings', // Title
			[ $this, 'print_section_info' ], // Callback
			'stitch-to-bricks' // Page
		);

		add_settings_field(
			'stb_stitch_api_key', // ID
			'API Key', // Title 
			[ $this, 'api_key_callback' ], // Callback
			'stitch-to-bricks', // Page
			'stb_setting_section_id' // Section           
		);
	}

	public function sanitize( $input ) {
		return sanitize_text_field( $input );
	}

	public function print_section_info() {
		echo 'Enter your Google Stitch API credentials below.';
	}

	public function api_key_callback() {
		$api_key = get_option( 'stb_stitch_api_key', '' );
		printf(
			'<input type="password" id="stb_stitch_api_key" name="stb_stitch_api_key" value="%s" class="regular-text" />',
			esc_attr( $api_key )
		);
	}
}
