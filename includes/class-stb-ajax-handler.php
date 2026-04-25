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
        add_action('wp_ajax_stb_list_projects', [$this, 'handle_list_projects']);
        add_action('wp_ajax_stb_list_screens', [$this, 'handle_list_screens']);
        add_action('wp_ajax_stb_fetch_screen_payload', [$this, 'handle_fetch_screen_payload']);
        add_action('wp_ajax_stb_ai_generate', [$this, 'handle_ai_generate']);
        add_action('wp_ajax_stb_ai_refine', [$this, 'handle_ai_refine']);
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

    private function stitch_client(): STB_Stitch_Client
    {
        static $client = null;

        if (!$client instanceof STB_Stitch_Client) {
            $client = new STB_Stitch_Client();
        }

        return $client;
    }

    private function get_import_payload(): array|WP_Error
    {
        $project_id = isset($_POST['project_id']) ? sanitize_text_field($_POST['project_id']) : '';
        $screen_id = isset($_POST['screen_id']) ? sanitize_text_field($_POST['screen_id']) : '';

        if (!$project_id || !$screen_id) {
            return new WP_Error('missing_screen_ids', 'project_id and screen_id are required.');
        }

        return $this->stitch_client()->import_screen($project_id, $screen_id);
    }

    // ── GET /projects ─────────────────────────────────────────────────────────
    public function handle_list_projects()
    {
        $this->verify_request();
        $projects = $this->stitch_client()->list_projects();

        if (is_wp_error($projects)) {
            wp_send_json_error($projects->get_error_message());
        }

        wp_send_json_success(['projects' => $projects]);
    }

    // ── GET /projects/:id/screens ─────────────────────────────────────────────
    public function handle_list_screens()
    {
        $this->verify_request();

        $project_id = isset($_POST['project_id']) ? sanitize_text_field($_POST['project_id']) : '';
        if (!$project_id) {
            wp_send_json_error('project_id is required.');
        }

        $screens = $this->stitch_client()->list_screens($project_id);

        if (is_wp_error($screens)) {
            wp_send_json_error($screens->get_error_message());
        }

        wp_send_json_success(['screens' => $screens]);
    }

    // ── POST /fetch-screen-payload ─────────────────────────────────────────────
    public function handle_fetch_screen_payload()
    {
        $this->verify_request();

        $data = $this->get_import_payload();

        if (is_wp_error($data)) {
            wp_send_json_error($data->get_error_message());
        }

        wp_send_json_success($data);
    }

    // ── AI Design Generation ─────────────────────────────────────────────────
    public function handle_ai_generate()
    {
        $this->verify_ai_request();

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : '';
        if (empty($prompt)) {
            wp_send_json_error('Prompt is required.');
        }

        $images = isset($_POST['images']) ? json_decode(wp_unslash($_POST['images']), true) : [];
        $conversation_history = isset($_POST['history']) ? json_decode(wp_unslash($_POST['history']), true) : [];

        $ai_client = $this->ai_client();
        $result = $ai_client->generate_design($prompt, $images, $conversation_history);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    // ── AI Design Refinement ─────────────────────────────────────────────────
    public function handle_ai_refine()
    {
        $this->verify_ai_request();

        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field(wp_unslash($_POST['feedback'])) : '';
        if (empty($feedback)) {
            wp_send_json_error('Feedback is required.');
        }

        $conversation_history = isset($_POST['history']) ? json_decode(wp_unslash($_POST['history']), true) : [];
        $images = isset($_POST['images']) ? json_decode(wp_unslash($_POST['images']), true) : [];

        $ai_client = $this->ai_client();
        $result = $ai_client->refine_design($conversation_history, $feedback, $images);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    // ── Shared: AI request verification ──────────────────────────────────────
    private function verify_ai_request()
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'stb_ai_nonce')) {
            wp_send_json_error('Invalid security token.', 403);
        }
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions.', 403);
        }

        $api_key = get_option('stb_openai_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error('OpenAI API key is not configured. Please set it in plugin settings.');
        }

        $daily_limit = (float) get_option('stb_ai_daily_limit', '5.00');
        if ($daily_limit > 0) {
            $usage = get_option('stb_ai_usage', ['total_cost' => 0.0, 'last_reset' => gmdate('Y-m-d')]);
            if ($usage['last_reset'] !== gmdate('Y-m-d')) {
                $usage['total_cost'] = 0.0;
                $usage['last_reset'] = gmdate('Y-m-d');
                update_option('stb_ai_usage', $usage);
            }
            if ($usage['total_cost'] >= $daily_limit) {
                wp_send_json_error('Daily cost limit of $' . number_format($daily_limit, 2) . ' reached. Please wait until tomorrow or increase the limit in settings.');
            }
        }
    }

    private function ai_client(): STB_AI_Client
    {
        static $client = null;

        if (!$client instanceof STB_AI_Client) {
            $client = new STB_AI_Client();
        }

        return $client;
    }
}
