<?php
/**
 * Integrates the Fetch button into the Bricks Builder UI.
 * 
 * The Bricks Builder has two contexts:
 *   - Main window (?bricks=run)           => runs main.min.js Vue app with the toolbar
 *   - Canvas iframe (?bricks=run&brickspreview=true) => runs iframe.min.js with the canvas
 * 
 * Our JS must load ONLY in the main window so it can inject into the Vue toolbar.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STB_Builder_UI
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_builder_scripts']);
    }

    /**
     * Enqueue scripts only within the Bricks Builder MAIN window (not the canvas iframe).
     */
    public function enqueue_builder_scripts()
    {
        // Only load in the main builder window — NOT the canvas iframe.
        // bricks_is_builder_main() returns true when ?bricks=run but NOT ?brickspreview.
        if (!function_exists('bricks_is_builder_main') || !bricks_is_builder_main()) {
            return;
        }

        wp_enqueue_style(
            'stb-builder-style',
            STB_PLUGIN_URL . 'assets/css/builder-style.css',
            [],
            STB_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'stb-builder-integration',
            STB_PLUGIN_URL . 'assets/js/builder-integration.js',
            [],
            STB_PLUGIN_VERSION,
            true // Load in footer, after Bricks' main.min.js has started the Vue app
        );

        // Localize script to pass the AJAX URL, nonce, and post ID
        global $post;
        wp_localize_script(
            'stb-builder-integration',
            'stbData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('stb_fetch_nonce'),
                'postId' => isset($post->ID) ? $post->ID : 0,
            ]
        );
    }
}
