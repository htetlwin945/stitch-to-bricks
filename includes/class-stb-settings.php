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
		add_action( 'wp_ajax_stb_test_ai_connection', [ $this, 'ajax_test_ai_connection' ] );
		add_action( 'wp_ajax_stb_generate_cheatsheet', [ $this, 'ajax_generate_cheatsheet' ] );
		add_action( 'wp_ajax_stb_save_design_files', [ $this, 'ajax_save_design_files' ] );
		add_action( 'wp_ajax_stb_upload_design_file', [ $this, 'ajax_upload_design_file' ] );
		add_action( 'parse_request', [ $this, 'serve_dynamic_css' ] );
	}

	public function serve_dynamic_css() {
		if ( isset( $_GET['stb_dynamic_css'] ) && $_GET['stb_dynamic_css'] === '1' ) {
			$css = get_option( 'stb_core_css', '' );
			if ( empty( $css ) ) {
				$css_path = STB_PLUGIN_DIR . 'core-framework-css (do not delete)/core-framework.css';
				if ( file_exists( $css_path ) ) {
					$css = file_get_contents( $css_path );
				}
			}
			header( 'Content-Type: text/css; charset=utf-8' );
			echo $css;
			exit;
		}
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
		$design_md_path = STB_PLUGIN_DIR . 'DESIGN.md';
		$css_path = STB_PLUGIN_DIR . 'core-framework-css (do not delete)/core-framework.css';
		$cheatsheet_path = STB_PLUGIN_DIR . 'core-framework-cheatsheet.md';

		$design_md = get_option( 'stb_design_md', file_exists( $design_md_path ) ? file_get_contents( $design_md_path ) : '' );
		$core_css = get_option( 'stb_core_css', file_exists( $css_path ) ? file_get_contents( $css_path ) : '' );
		$cheatsheet = get_option( 'stb_cheatsheet', file_exists( $cheatsheet_path ) ? file_get_contents( $cheatsheet_path ) : '' );
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

			<hr style="margin: 2em 0;" />

			<h2>AI Designer Settings</h2>
			<p>Configure OpenAI API credentials for the AI-powered design generator.</p>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'stb_option_group' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">OpenAI API Key</th>
						<td>
							<input type="password" id="stb_openai_api_key" name="stb_openai_api_key" value="<?php echo esc_attr( get_option( 'stb_openai_api_key', '' ) ); ?>" class="regular-text" />
							<p class="description">Your OpenAI API key. Get one at <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></p>
						</td>
					</tr>
					<tr>
						<th scope="row">Model</th>
						<td>
							<select id="stb_openai_model" name="stb_openai_model">
								<option value="gpt-4o-mini" <?php selected( get_option( 'stb_openai_model', 'gpt-4o-mini' ), 'gpt-4o-mini' ); ?>>GPT-4o Mini (Faster, Cheaper)</option>
								<option value="gpt-4o" <?php selected( get_option( 'stb_openai_model', 'gpt-4o-mini' ), 'gpt-4o' ); ?>>GPT-4o (More Capable)</option>
							</select>
							<p class="description">Choose the AI model. GPT-4o Mini is recommended for testing.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Test Connection</th>
						<td>
							<button type="button" id="stb-test-connection" class="button">Test API Connection</button>
							<span id="stb-test-result" style="margin-left: 10px;"></span>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save AI Settings' ); ?>
			</form>

			<hr style="margin: 2em 0;" />

			<h2>Design System Configuration</h2>
			<p>Manage your design contract and framework files. Upload or edit them directly, then generate an AI-optimized cheatsheet.</p>

			<div id="stb-design-files-ui">
				<div class="stb-file-group">
					<h3>DESIGN.md</h3>
					<p class="description">The master design contract. Defines visual language, tokens, and rules.</p>
					<div class="stb-file-actions">
						<label class="button">Upload File <input type="file" id="stb-upload-design" accept=".md" style="display: none;" /></label>
						<button type="button" id="stb-reset-design" class="button">Reset to Default</button>
					</div>
					<textarea id="stb-design-md-content" class="large-text code" rows="10"><?php echo esc_textarea( $design_md ); ?></textarea>
				</div>

				<div class="stb-file-group">
					<h3>Core Framework CSS</h3>
					<p class="description">The actual stylesheet used for preview rendering and AI context.</p>
					<div class="stb-file-actions">
						<label class="button">Upload File <input type="file" id="stb-upload-css" accept=".css" style="display: none;" /></label>
						<button type="button" id="stb-reset-css" class="button">Reset to Default</button>
					</div>
					<textarea id="stb-core-css-content" class="large-text code" rows="10"><?php echo esc_textarea( $core_css ); ?></textarea>
				</div>

				<div class="stb-file-group">
					<h3>Core Framework Cheatsheet</h3>
					<p class="description">AI-optimized reference guide. Auto-generated from your CSS.</p>
					<div class="stb-file-actions">
						<button type="button" id="stb-generate-cheatsheet" class="button button-primary">Generate Cheatsheet via AI</button>
						<span id="stb-generate-result" style="margin-left: 10px;"></span>
					</div>
					<textarea id="stb-cheatsheet-content" class="large-text code" rows="10"><?php echo esc_textarea( $cheatsheet ); ?></textarea>
				</div>

				<button type="button" id="stb-save-design-files" class="button button-primary button-hero">Save All Design Files</button>
				<span id="stb-save-result" style="margin-left: 10px; font-weight: 600;"></span>
			</div>
		</div>

		<script>
			jQuery(document).ready(function($) {
				// Test Connection
				$('#stb-test-connection').on('click', function() {
					var $btn = $(this);
					var $result = $('#stb-test-result');
					var apiKey = $('#stb_openai_api_key').val();
					
					if (!apiKey) {
						$result.html('<span style="color: #dc3232;">✗ Please enter an API key first</span>');
						return;
					}
					
					$btn.prop('disabled', true).text('Testing...');
					$result.html('');
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: { action: 'stb_test_ai_connection', api_key: apiKey, nonce: '<?php echo wp_create_nonce( 'stb_test_ai_connection' ); ?>' },
						success: function(res) {
							$result.html(res.success ? '<span style="color: #46b450;">✓ ' + res.data + '</span>' : '<span style="color: #dc3232;">✗ ' + res.data + '</span>');
						},
						error: function(xhr) {
							$result.html('<span style="color: #dc3232;">✗ ' + (xhr.responseJSON ? xhr.responseJSON.data : 'Request failed') + '</span>');
						},
						complete: function() { $btn.prop('disabled', false).text('Test API Connection'); }
					});
				});

				// Generate Cheatsheet
				$('#stb-generate-cheatsheet').on('click', function() {
					var $btn = $(this);
					var $result = $('#stb-generate-result');
					var css = $('#stb-core-css-content').val();
					
					if (!css) {
						$result.html('<span style="color: #dc3232;">✗ CSS content is required</span>');
						return;
					}
					
					$btn.prop('disabled', true).text('Generating...');
					$result.html('');
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: { action: 'stb_generate_cheatsheet', css_content: css, nonce: '<?php echo wp_create_nonce( 'stb_ai_nonce' ); ?>' },
						success: function(res) {
							if (res.success) {
								$('#stb-cheatsheet-content').val(res.data);
								$result.html('<span style="color: #46b450;">✓ Generated successfully</span>');
							} else {
								$result.html('<span style="color: #dc3232;">✗ ' + res.data + '</span>');
							}
						},
						error: function(xhr) {
							$result.html('<span style="color: #dc3232;">✗ ' + (xhr.responseJSON ? xhr.responseJSON.data : 'Failed') + '</span>');
						},
						complete: function() { $btn.prop('disabled', false).text('Generate Cheatsheet via AI'); }
					});
				});

				// Save Files
				$('#stb-save-design-files').on('click', function() {
					var $btn = $(this);
					var $result = $('#stb-save-result');
					
					$btn.prop('disabled', true).text('Saving...');
					$result.html('');
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'stb_save_design_files',
							design_md: $('#stb-design-md-content').val(),
							core_css: $('#stb-core-css-content').val(),
							cheatsheet: $('#stb-cheatsheet-content').val(),
							nonce: '<?php echo wp_create_nonce( 'stb_ai_nonce' ); ?>'
						},
						success: function(res) {
							$result.html(res.success ? '<span style="color: #46b450;">✓ All files saved successfully</span>' : '<span style="color: #dc3232;">✗ ' + res.data + '</span>');
						},
						error: function() {
							$result.html('<span style="color: #dc3232;">✗ Save failed</span>');
						},
						complete: function() { $btn.prop('disabled', false).text('Save All Design Files'); }
					});
				});

				// File Uploads
				$('#stb-upload-design').on('change', function() { handleFileUpload(this, '#stb-design-md-content'); });
				$('#stb-upload-css').on('change', function() { handleFileUpload(this, '#stb-core-css-content'); });

				function handleFileUpload(input, textareaId) {
					var file = input.files[0];
					if (!file) return;
					var reader = new FileReader();
					reader.onload = function(e) { $(textareaId).val(e.target.result); };
					reader.readAsText(file);
				}

				// Reset to Default
				$('#stb-reset-design').on('click', function() {
					if (confirm('Reset DESIGN.md to the bundled default?')) {
						$.post(ajaxurl, { action: 'stb_upload_design_file', file: 'design_md', nonce: '<?php echo wp_create_nonce( 'stb_upload_nonce' ); ?>' }, function(res) {
							if (res.success) $('#stb-design-md-content').val(res.data);
						});
					}
				});
				$('#stb-reset-css').on('click', function() {
					if (confirm('Reset Core Framework CSS to the bundled default?')) {
						$.post(ajaxurl, { action: 'stb_upload_design_file', file: 'core_css', nonce: '<?php echo wp_create_nonce( 'stb_upload_nonce' ); ?>' }, function(res) {
							if (res.success) $('#stb-core-css-content').val(res.data);
						});
					}
				});
			});
		</script>
		<?php
	}

	public function ajax_test_ai_connection() {
		check_ajax_referer( 'stb_test_ai_connection', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( empty( $api_key ) ) wp_send_json_error( 'API key is required' );

		require_once STB_PLUGIN_DIR . 'includes/class-stb-ai-client.php';
		$client = new STB_AI_Client();
		$result = $client->test_connection( $api_key );
		
		if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
		wp_send_json_success( 'Connection successful! API key is valid.' );
	}

	public function ajax_generate_cheatsheet() {
		check_ajax_referer( 'stb_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$css = isset( $_POST['css_content'] ) ? wp_unslash( $_POST['css_content'] ) : '';
		if ( empty( $css ) ) wp_send_json_error( 'CSS content is required.' );

		require_once STB_PLUGIN_DIR . 'includes/class-stb-ai-client.php';
		$client = new STB_AI_Client();
		$result = $client->generate_cheatsheet( $css );
		
		if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
		wp_send_json_success( $result );
	}

	public function ajax_save_design_files() {
		check_ajax_referer( 'stb_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$design_md = isset( $_POST['design_md'] ) ? wp_unslash( $_POST['design_md'] ) : '';
		$core_css = isset( $_POST['core_css'] ) ? wp_unslash( $_POST['core_css'] ) : '';
		$cheatsheet = isset( $_POST['cheatsheet'] ) ? wp_unslash( $_POST['cheatsheet'] ) : '';

		update_option( 'stb_design_md', $design_md );
		update_option( 'stb_core_css', $core_css );
		update_option( 'stb_cheatsheet', $cheatsheet );

		wp_send_json_success( 'All design files saved to database successfully.' );
	}

	public function ajax_upload_design_file() {
		check_ajax_referer( 'stb_upload_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$file_type = isset( $_POST['file'] ) ? sanitize_text_field( $_POST['file'] ) : '';
		$default_path = '';

		if ( $file_type === 'design_md' ) {
			$default_path = STB_PLUGIN_DIR . 'DESIGN.md';
		} elseif ( $file_type === 'core_css' ) {
			$default_path = STB_PLUGIN_DIR . 'core-framework-css (do not delete)/core-framework.css';
		}

		if ( ! file_exists( $default_path ) ) {
			wp_send_json_error( 'Default file not found.' );
		}

		wp_send_json_success( file_get_contents( $default_path ) );
	}

	public function page_init() {
		register_setting( 'stb_option_group', 'stb_stitch_api_key', [ $this, 'sanitize' ] );
		register_setting( 'stb_option_group', 'stb_openai_api_key', [ $this, 'sanitize' ] );
		register_setting( 'stb_option_group', 'stb_openai_model', [ $this, 'sanitize' ] );
		register_setting( 'stb_option_group', 'stb_ai_daily_limit', [ $this, 'sanitize' ] );
		register_setting( 'stb_option_group', 'stb_design_md', [ $this, 'sanitize_large' ] );
		register_setting( 'stb_option_group', 'stb_core_css', [ $this, 'sanitize_large' ] );
		register_setting( 'stb_option_group', 'stb_cheatsheet', [ $this, 'sanitize_large' ] );

		add_settings_section( 'stb_setting_section_id', 'Google Stitch API Settings', [ $this, 'print_section_info' ], 'stitch-to-bricks' );
		add_settings_field( 'stb_stitch_api_key', 'API Key', [ $this, 'api_key_callback' ], 'stitch-to-bricks', 'stb_setting_section_id' );
		add_settings_section( 'stb_ai_designer_section', 'AI Designer (OpenAI)', [ $this, 'print_ai_section_info' ], 'stitch-to-bricks' );
		add_settings_field( 'stb_ai_daily_limit', 'Daily Cost Limit ($)', [ $this, 'daily_limit_callback' ], 'stitch-to-bricks', 'stb_ai_designer_section' );
	}

	public function sanitize( $input ) { return sanitize_text_field( $input ); }
	public function sanitize_large( $input ) { return wp_kses_post( $input ); }
	public function print_section_info() { echo 'Enter your Google Stitch API credentials below.'; }
	
	public function print_ai_section_info() {
		$usage = get_option( 'stb_ai_usage', [ 'total_tokens' => 0, 'total_cost' => 0.0, 'requests' => 0, 'last_reset' => gmdate( 'Y-m-d' ) ] );
		echo 'Configure OpenAI API for AI-powered design generation. Settings are saved automatically via AJAX.';
		echo '<div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #c3c4c7; border-radius: 4px;">';
		echo '<strong>Today\'s Usage:</strong> ';
		echo number_format( $usage['total_tokens'] ) . ' tokens | $' . number_format( $usage['total_cost'], 6 ) . ' | ' . $usage['requests'] . ' requests';
		echo '<br><small>Resets daily at midnight UTC.</small>';
		echo '</div>';
	}

	public function api_key_callback() {
		$api_key = get_option( 'stb_stitch_api_key', '' );
		printf( '<input type="password" id="stb_stitch_api_key" name="stb_stitch_api_key" value="%s" class="regular-text" />', esc_attr( $api_key ) );
	}

	public function daily_limit_callback() {
		$limit = get_option( 'stb_ai_daily_limit', '5.00' );
		printf( '<input type="number" step="0.01" id="stb_ai_daily_limit" name="stb_ai_daily_limit" value="%s" class="small-text" />', esc_attr( $limit ) );
		echo '<p class="description">Set a daily spending limit to prevent unexpected charges. Set to 0 to disable limit.</p>';
	}
}
