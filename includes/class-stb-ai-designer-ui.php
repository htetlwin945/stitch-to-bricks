<?php
/**
 * AI Designer UI - Admin Page & Asset Loading
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STB_AI_Designer_UI {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_designer_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_stb_upload_image', [ $this, 'ajax_upload_image' ] );
	}

	public function add_designer_page() {
		add_submenu_page(
			'edit.php?post_type=stb_design',
			'AI Designer',
			'AI Designer',
			'edit_posts',
			'stb-ai-designer',
			[ $this, 'render_designer_page' ]
		);
	}

	public function enqueue_assets( $hook ) {
		if ( $hook !== 'stb_design_page_stb-ai-designer' ) {
			return;
		}

		wp_enqueue_style(
			'stb-ai-designer',
			STB_PLUGIN_URL . 'assets/css/ai-designer.css',
			[],
			STB_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'stb-ai-designer',
			STB_PLUGIN_URL . 'assets/js/ai-designer.js',
			[ 'jquery' ],
			STB_PLUGIN_VERSION,
			true
		);

		wp_localize_script( 'stb-ai-designer', 'stbAi', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'stb_ai_nonce' ),
			'cfCssUrl'      => home_url( '?stb_dynamic_css=1' ),
			'uploadNonce'   => wp_create_nonce( 'stb_upload_nonce' ),
			'maxImageSize'  => 5 * 1024 * 1024,
		] );
	}

	public function render_designer_page() {
		?>
		<div class="wrap stb-ai-designer-wrap">
			<h1 class="wp-heading-inline">AI Designer</h1>
			<p class="description">Generate Core Framework-compliant designs using OpenAI. Describe what you want or upload a reference image.</p>

			<div class="stb-designer-container">
				<div class="stb-designer-sidebar">
					<div class="stb-chat-panel">
						<div class="stb-chat-header">
							<h3>Conversation</h3>
							<button type="button" id="stb-clear-chat" class="button button-small">Clear</button>
						</div>
						<div id="stb-chat-messages" class="stb-chat-messages"></div>
						<div class="stb-chat-input-area">
							<div id="stb-image-preview" class="stb-image-preview" style="display: none;">
								<img id="stb-preview-img" src="" alt="Preview" />
								<button type="button" id="stb-remove-image" class="stb-remove-image">&times;</button>
							</div>
							<div class="stb-input-row">
								<textarea id="stb-prompt-input" placeholder="Describe your design... (e.g., 'Hero section with primary button and badge')" rows="3"></textarea>
								<div class="stb-input-actions">
									<label for="stb-image-upload" class="stb-upload-btn" title="Upload reference image">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
										<input type="file" id="stb-image-upload" accept="image/*" style="display: none;" />
									</label>
									<button type="button" id="stb-generate-btn" class="button button-primary">Generate</button>
								</div>
							</div>
						</div>
					</div>

					<div class="stb-design-library">
						<div class="stb-library-header">
							<h3>Saved Designs</h3>
							<button type="button" id="stb-refresh-library" class="button button-small">Refresh</button>
						</div>
						<div id="stb-library-list" class="stb-library-list">
							<p class="stb-empty-state">No saved designs yet.</p>
						</div>
					</div>
				</div>

				<div class="stb-designer-main">
					<div class="stb-preview-container">
					<div class="stb-preview-toolbar">
						<div class="stb-toolbar-left">
							<span id="stb-design-status" class="stb-status">Ready</span>
							<span id="stb-token-info" class="stb-token-info"></span>
							<button type="button" id="stb-compare-btn" class="button button-small" style="display: none;">Show Previous</button>
						</div>
						<div class="stb-toolbar-right">
							<input type="text" id="stb-design-title" placeholder="Design name..." class="regular-text" />
							<button type="button" id="stb-save-btn" class="button" disabled>Save</button>
							<button type="button" id="stb-copy-bricks-btn" class="button button-primary" disabled>Copy to Bricks</button>
						</div>
					</div>
					<div id="stb-feedback-suggestions" class="stb-feedback-suggestions"></div>
						<div class="stb-preview-wrapper">
							<iframe id="stb-preview-iframe" sandbox="allow-same-origin" title="Design Preview"></iframe>
						</div>
					</div>
				</div>
			</div>

			<div id="stb-copy-modal" class="stb-modal" style="display: none;">
				<div class="stb-modal-content">
					<h3>Import to Bricks</h3>
					<p>HTML has been copied to your clipboard. Follow these steps:</p>
					<ol>
						<li>Open the Bricks Builder on your page</li>
						<li>Right-click in the canvas and select <strong>"HTML & CSS to Bricks"</strong></li>
						<li>Paste the HTML (Ctrl+V / Cmd+V)</li>
						<?php if ( ! empty( $css ) ) : ?><li>If styling needs adjustment, paste the CSS in the custom CSS panel</li><?php endif; ?>
					</ol>
					<button type="button" class="button button-primary" onclick="jQuery('#stb-copy-modal').hide()">Got it</button>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_upload_image() {
		check_ajax_referer( 'stb_upload_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		if ( empty( $_FILES['image'] ) ) {
			wp_send_json_error( 'No image uploaded.' );
		}

		$file = $_FILES['image'];

		if ( $file['size'] > 5 * 1024 * 1024 ) {
			wp_send_json_error( 'Image must be less than 5MB.' );
		}

		$allowed_types = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
		if ( ! in_array( $file['type'], $allowed_types, true ) ) {
			wp_send_json_error( 'Only JPG, PNG, WebP, and GIF images are allowed.' );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'image', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		}

		$image_url = wp_get_attachment_url( $attachment_id );
		$image_base64 = $this->get_image_base64( $attachment_id );

		wp_send_json_success( [
			'url'     => $image_url,
			'base64'  => $image_base64,
			'mime'    => $file['type'],
			'id'      => $attachment_id,
		] );
	}

	private function get_image_base64( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );
		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		$image_data = file_get_contents( $file_path );
		$mime_type = get_post_mime_type( $attachment_id );

		return base64_encode( $image_data );
	}
}
