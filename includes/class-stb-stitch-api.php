<?php
/**
 * Service to interact with the Google Stitch API
 */

if (!defined('ABSPATH')) {
    exit;
}

class STB_Stitch_API
{

    private $api_base_url;

    /**
     * Returns the Node proxy base URL from env/constant/default.
     */
    public static function get_proxy_url(): string
    {
        $env_url = getenv('STB_NODE_PROXY_URL');
        if ($env_url)
            return rtrim($env_url, '/') . '/';
        if (defined('STB_NODE_PROXY_URL'))
            return rtrim(STB_NODE_PROXY_URL, '/') . '/';
        return 'http://127.0.0.1:3000/';
    }

    public function __construct()
    {
        $this->api_base_url = self::get_proxy_url();
    }

    /**
     * Fetches the HTML template from Stitch.
     * 
     * @return string|WP_Error Raw HTML string or WP_Error on failure.
     */
    public function fetch_latest_template()
    {
        // Example endpoint to our local Node.js MCP Proxy
        $endpoint = rtrim($this->api_base_url, '/') . '/generate';

        // For v1.0, we just send a generic prompt to get a layout, or if the user passed one, we'd use it here.
        $prompt = "Generate a standard landing page layout with a hero section, feature blocks, and a call to action.";

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'prompt' => $prompt
            ]),
            'timeout' => 60, // Stitch generation via MCP takes time
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if (200 !== $status_code) {
            return new WP_Error('api_error', 'Node MCP Proxy Error: ' . $status_code . ' - ' . $body);
        }

        $data = json_decode($body, true);

        if (!isset($data['success']) || !$data['success'] || !isset($data['html'])) {
            return new WP_Error('invalid_response', 'Proxy returned an invalid response missing the HTML payload.');
        }

        return $data['html'];
    }
}
