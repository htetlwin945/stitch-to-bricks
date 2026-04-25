<?php
/**
 * Design Manager - Custom Post Type & Storage
 * 
 * Registers stb_design CPT and handles saving, loading, and listing
 * AI-generated designs with sanitization and version tracking.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STB_Design_Manager {

	public function __construct() {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'wp_ajax_stb_save_design', [ $this, 'ajax_save_design' ] );
		add_action( 'wp_ajax_stb_load_design', [ $this, 'ajax_load_design' ] );
		add_action( 'wp_ajax_stb_list_designs', [ $this, 'ajax_list_designs' ] );
		add_action( 'wp_ajax_stb_delete_design', [ $this, 'ajax_delete_design' ] );
	}

	public function register_cpt() {
		$labels = [
			'name'               => 'AI Designs',
			'singular_name'      => 'AI Design',
			'menu_name'          => 'AI Designs',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Design',
			'edit_item'          => 'Edit Design',
			'new_item'           => 'New Design',
			'view_item'          => 'View Design',
			'search_items'       => 'Search Designs',
			'not_found'          => 'No designs found',
			'not_found_in_trash' => 'No designs found in Trash',
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [ 'title', 'editor', 'revisions' ],
			'menu_icon'           => 'dashicons-art',
			'menu_position'       => 25,
			'rewrite'             => false,
			'show_in_rest'        => false,
		];

		register_post_type( 'stb_design', $args );
	}

	public function register_meta() {
		register_post_meta( 'stb_design', 'html_content', [
			'show_in_rest'      => false,
			'single'            => true,
			'type'              => 'string',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );

		register_post_meta( 'stb_design', 'css_content', [
			'show_in_rest'      => false,
			'single'            => true,
			'type'              => 'string',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );

		register_post_meta( 'stb_design', 'prompt_history', [
			'show_in_rest'      => false,
			'single'            => true,
			'type'              => 'string',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );

		register_post_meta( 'stb_design', 'reference_image_url', [
			'show_in_rest'      => false,
			'single'            => true,
			'type'              => 'string',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );

		register_post_meta( 'stb_design', 'model_used', [
			'show_in_rest'      => false,
			'single'            => true,
			'type'              => 'string',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );

		register_post_meta( 'stb_design', 'token_cost', [
			'show_in_rest'      => false,
			'single'            => true,
			'type'              => 'string',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );

		register_post_meta( 'stb_design', 'version_number', [
			'show_in_rest'      => false,
			'single'            => true,
			'type'              => 'integer',
			'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
		] );
	}

	public function ajax_save_design() {
		$this->verify_request();

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : 'Untitled Design';
		$html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : '';
		$css = isset( $_POST['css'] ) ? wp_unslash( $_POST['css'] ) : '';
		$prompt_history = isset( $_POST['prompt_history'] ) ? wp_unslash( $_POST['prompt_history'] ) : '';
		$image_url = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$model = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
		$token_cost = isset( $_POST['token_cost'] ) ? sanitize_text_field( wp_unslash( $_POST['token_cost'] ) ) : '';
		$design_id = isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0;

		$sanitized_html = $this->sanitize_html( $html );
		$sanitized_css = $this->sanitize_css( $css );

		if ( $design_id && get_post_type( $design_id ) === 'stb_design' ) {
			$post_id = wp_update_post( [
				'ID'         => $design_id,
				'post_title' => $title,
			], true );
		} else {
			$post_id = wp_insert_post( [
				'post_title'   => $title,
				'post_type'    => 'stb_design',
				'post_status'  => 'publish',
			], true );
		}

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( $post_id->get_error_message() );
		}

		$current_version = (int) get_post_meta( $post_id, 'version_number', true );
		$new_version = $current_version + 1;

		update_post_meta( $post_id, 'html_content', $sanitized_html );
		update_post_meta( $post_id, 'css_content', $sanitized_css );
		update_post_meta( $post_id, 'prompt_history', $prompt_history );
		update_post_meta( $post_id, 'reference_image_url', $image_url );
		update_post_meta( $post_id, 'model_used', $model );
		update_post_meta( $post_id, 'token_cost', $token_cost );
		update_post_meta( $post_id, 'version_number', $new_version );

		wp_send_json_success( [
			'design_id'     => $post_id,
			'version'       => $new_version,
			'html'          => $sanitized_html,
			'css'           => $sanitized_css,
			'edit_url'      => get_edit_post_link( $post_id, 'raw' ),
		] );
	}

	public function ajax_load_design() {
		$this->verify_request();

		$design_id = isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0;
		if ( ! $design_id ) {
			wp_send_json_error( 'Design ID is required.' );
		}

		if ( get_post_type( $design_id ) !== 'stb_design' ) {
			wp_send_json_error( 'Design not found.' );
		}

		$design = [
			'id'                => $design_id,
			'title'             => get_the_title( $design_id ),
			'html'              => get_post_meta( $design_id, 'html_content', true ),
			'css'               => get_post_meta( $design_id, 'css_content', true ),
			'prompt_history'    => get_post_meta( $design_id, 'prompt_history', true ),
			'image_url'         => get_post_meta( $design_id, 'reference_image_url', true ),
			'model'             => get_post_meta( $design_id, 'model_used', true ),
			'token_cost'        => get_post_meta( $design_id, 'token_cost', true ),
			'version'           => get_post_meta( $design_id, 'version_number', true ),
			'date'              => get_the_date( 'Y-m-d H:i:s', $design_id ),
			'modified'          => get_the_modified_date( 'Y-m-d H:i:s', $design_id ),
		];

		wp_send_json_success( $design );
	}

	public function ajax_list_designs() {
		$this->verify_request();

		$page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$args = [
			'post_type'      => 'stb_design',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query = new WP_Query( $args );
		$designs = [];

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$designs[] = [
					'id'          => $post->ID,
					'title'       => $post->post_title,
					'version'     => get_post_meta( $post->ID, 'version_number', true ),
					'model'       => get_post_meta( $post->ID, 'model_used', true ),
					'token_cost'  => get_post_meta( $post->ID, 'token_cost', true ),
					'date'        => get_the_date( 'Y-m-d H:i:s', $post->ID ),
					'modified'    => get_the_modified_date( 'Y-m-d H:i:s', $post->ID ),
					'edit_url'    => get_edit_post_link( $post->ID, 'raw' ),
				];
			}
		}

		wp_reset_postdata();

		wp_send_json_success( [
			'designs'    => $designs,
			'total'      => $query->found_posts,
			'pages'      => $query->max_num_pages,
			'current'    => $page,
		] );
	}

	public function ajax_delete_design() {
		$this->verify_request();

		$design_id = isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0;
		if ( ! $design_id ) {
			wp_send_json_error( 'Design ID is required.' );
		}

		if ( get_post_type( $design_id ) !== 'stb_design' ) {
			wp_send_json_error( 'Design not found.' );
		}

		$result = wp_delete_post( $design_id, true );

		if ( ! $result ) {
			wp_send_json_error( 'Failed to delete design.' );
		}

		wp_send_json_success( 'Design deleted.' );
	}

	private function sanitize_html( $html ) {
		$html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
		$html = preg_replace( '/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $html );
		$html = preg_replace( '/\bon\w+\s*=\s*\S+/i', '', $html );
		$html = preg_replace( '/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>/i', '', $html );
		$html = preg_replace( '/<style[^>]*>@import[^;]*;[^<]*<\/style>/is', '', $html );

		return $html;
	}

	private function sanitize_css( $css ) {
		$css = preg_replace( '/@import\s+[^;]+;/i', '', $css );
		$css = preg_replace( '/url\(\s*["\']?\s*https?:\/\/[^)]+["\']?\s*\)/i', '', $css );

		return trim( $css );
	}

	private function verify_request() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'stb_ai_nonce' ) ) {
			wp_send_json_error( 'Invalid security token.', 403 );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient permissions.', 403 );
		}
	}
}
