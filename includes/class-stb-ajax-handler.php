<?php
/**
 * Handles AJAX requests from the Bricks Builder UI
 */

if (!defined('ABSPATH')) {
    exit;
}

class STB_Ajax_Handler
{

    public function __construct()
    {
        add_action('wp_ajax_stb_fetch_template', [$this, 'handle_fetch_request']);
        add_action('wp_ajax_stb_list_projects', [$this, 'handle_list_projects']);
        add_action('wp_ajax_stb_list_screens', [$this, 'handle_list_screens']);
        add_action('wp_ajax_stb_import_screen', [$this, 'handle_import_screen']);
        add_action('wp_ajax_stb_save_to_page', [$this, 'handle_save_to_page']);
    }

    // ── Shared: verify nonce + capability ────────────────────────────────────
    private function verify_request()
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'stb_fetch_nonce')) {
            wp_send_json_error('Invalid security token.', 403);
        }
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions.', 403);
        }
    }

    // ── Shared: proxy GET call to Node server ─────────────────────────────────
    private function proxy_get(string $path): array|WP_Error
    {
        $node_url = STB_Stitch_API::get_proxy_url();
        $url = trailingslashit($node_url) . ltrim($path, '/');

        stb_log("STB proxy GET: $url");

        $response = wp_remote_get($url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body ?: new WP_Error('json_error', 'Could not decode proxy response');
    }

    // ── Shared: proxy POST call to Node server ────────────────────────────────
    private function proxy_post(string $path, array $body): array|WP_Error
    {
        $node_url = STB_Stitch_API::get_proxy_url();
        $url = trailingslashit($node_url) . ltrim($path, '/');

        stb_log("STB proxy POST: $url");

        $response = wp_remote_post($url, [
            'timeout' => 60,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        return $decoded ?: new WP_Error('json_error', 'Could not decode proxy response');
    }

    // ── GET /projects ─────────────────────────────────────────────────────────
    public function handle_list_projects()
    {
        $this->verify_request();
        $data = $this->proxy_get('projects');

        if (is_wp_error($data)) {
            wp_send_json_error($data->get_error_message());
        }
        wp_send_json_success($data);
    }

    // ── GET /projects/:id/screens ─────────────────────────────────────────────
    public function handle_list_screens()
    {
        $this->verify_request();

        $project_id = isset($_POST['project_id']) ? sanitize_text_field($_POST['project_id']) : '';
        if (!$project_id) {
            wp_send_json_error('project_id is required.');
        }

        $data = $this->proxy_get("projects/{$project_id}/screens");

        if (is_wp_error($data)) {
            wp_send_json_error($data->get_error_message());
        }
        wp_send_json_success($data);
    }

    // ── POST /import-screen ───────────────────────────────────────────────────
    public function handle_import_screen()
    {
        $this->verify_request();

        $project_id = isset($_POST['project_id']) ? sanitize_text_field($_POST['project_id']) : '';
        $screen_id = isset($_POST['screen_id']) ? sanitize_text_field($_POST['screen_id']) : '';

        if (!$project_id || !$screen_id) {
            wp_send_json_error('project_id and screen_id are required.');
        }

        $data = $this->proxy_post('import-screen', [
            'projectId' => $project_id,
            'screenId' => $screen_id,
        ]);

        if (is_wp_error($data)) {
            wp_send_json_error($data->get_error_message());
        }

        // Pass HTML through converter — get full Bricks clipboard format
        if (!empty($data['html'])) {
            $title = $data['title'] ?? '';
            $screenshot = $data['screenshotUrl'] ?? '';   // Stitch screen screenshot URL
            $width = $data['width'] ?? '';
            $height = $data['height'] ?? '';

            $meta = [
                'screenshotUrl' => $screenshot,
                'width' => $width,
                'height' => $height,
            ];

            $converter = new STB_Converter();
            $clipboard = $converter->convert($data['html'], $title, $meta);

            if ($clipboard && !empty($clipboard['content'])) {
                $data['elements'] = $clipboard['content'];
                $data['clipboard'] = $clipboard;
                $data['globalClasses'] = $clipboard['globalClasses'];
                stb_log('STB: Converter produced ' . count($clipboard['content']) . ' elements and '
                    . count($clipboard['globalClasses']) . ' global classes for "' . $title . '"');
            } else {
                stb_log('STB: Converter returned empty content for "' . $title . '"');
            }
        }

        wp_send_json_success($data);
    }

    // ── Legacy: fetch template (kept for backward compat) ─────────────────────
    public function handle_fetch_request()
    {
        $this->verify_request();

        $api_service = new STB_Stitch_API();
        $html_payload = $api_service->fetch_latest_template();

        if (is_wp_error($html_payload)) {
            wp_send_json_error($html_payload->get_error_message());
        }

        $parser = new STB_Parser();
        $elements = $parser->parse_html_to_bricks($html_payload);

        if (is_wp_error($elements)) {
            wp_send_json_error($elements->get_error_message());
        }

        wp_send_json_success(['elements' => $elements]);
    }

    // ── Save parsed Bricks elements directly to post meta ─────────────────────
    public function handle_save_to_page()
    {
        $this->verify_request();

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $elements = isset($_POST['elements']) ? stripslashes($_POST['elements']) : '';
        $global_classes = isset($_POST['globalClasses']) ? stripslashes($_POST['globalClasses']) : '[]';

        if (!$post_id || !$elements) {
            wp_send_json_error('post_id and elements are required.');
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('You cannot edit this post.', 403);
        }

        $decoded_elements = json_decode($elements, true);
        if (!is_array($decoded_elements)) {
            wp_send_json_error('Invalid elements JSON. Decode failed.');
        }

        $decoded_classes = json_decode($global_classes, true);
        if (!is_array($decoded_classes)) {
            $decoded_classes = [];
        }

        // ── Save elements (core page content) ─────────────────────────────────
        // Bricks stores elements in _bricks_page_content_2
        $updated = update_post_meta($post_id, '_bricks_page_content_2', $decoded_elements);

        // ── Save global classes into Bricks site-wide class library ────────────
        // CRITICAL: Bricks global classes are stored in wp_options, not post_meta.
        // The option key is 'bricks_global_classes' and holds a flat array of class objects.
        // We merge new classes in so we never destroy existing CF classes.
        if (!empty($decoded_classes)) {
            $existing_classes = get_option('bricks_global_classes', []);
            if (!is_array($existing_classes)) {
                $existing_classes = [];
            }

            // Index existing by ID for fast merge
            $class_map = [];
            foreach ($existing_classes as $cls) {
                if (!empty($cls['id'])) {
                    $class_map[$cls['id']] = $cls;
                }
            }

            // Add/overwrite with our new converter classes
            foreach ($decoded_classes as $cls) {
                if (!empty($cls['id'])) {
                    $class_map[$cls['id']] = $cls;
                }
            }

            update_option('bricks_global_classes', array_values($class_map), false);
            stb_log("STB: Merged " . count($decoded_classes) . " classes into bricks_global_classes (total: " . count($class_map) . ").");
        }

        // Ensure Bricks knows this page has bricks content
        update_post_meta($post_id, '_bricks_editor_mode', 'bricks');

        stb_log("STB: Saved " . count($decoded_elements) . " Bricks elements to post {$post_id}. update_post_meta result: " . var_export($updated, true));

        wp_send_json_success([
            'saved' => true,
            'post_id' => $post_id,
            'count' => count($decoded_elements),
            'classes_saved' => count($decoded_classes),
            'message' => count($decoded_elements) . ' elements and ' . count($decoded_classes) . ' global classes saved.',
        ]);
    }
}

