<?php
/**
 * Low-level Stitch MCP client for WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STB_Stitch_Client
{
    private const DEFAULT_MCP_URL = 'https://stitch.googleapis.com/mcp';

    /**
     * Resolve the Stitch API key from server-side configuration.
     */
    public static function get_api_key(): string|WP_Error
    {
        $env_key = getenv('STITCH_API_KEY');
        if (is_string($env_key) && $env_key !== '') {
            return $env_key;
        }

        if (defined('STITCH_API_KEY') && STITCH_API_KEY) {
            return (string) STITCH_API_KEY;
        }

        $stored_key = get_option('stb_stitch_api_key', '');
        if (is_string($stored_key) && $stored_key !== '') {
            return $stored_key;
        }

        return new WP_Error('missing_stitch_api_key', 'Stitch API key is not configured.');
    }

    /**
     * Resolve the Stitch MCP endpoint.
     */
    public static function get_mcp_url(): string
    {
        $env_url = getenv('STITCH_HOST');
        if (is_string($env_url) && $env_url !== '') {
            return rtrim($env_url, '/');
        }

        if (defined('STITCH_HOST') && STITCH_HOST) {
            return rtrim((string) STITCH_HOST, '/');
        }

        return self::DEFAULT_MCP_URL;
    }

    /**
     * Call a Stitch MCP tool over JSON-RPC.
     */
    public function call_tool(string $tool_name, array $arguments = []): array|string|WP_Error
    {
        $api_key = self::get_api_key();
        if (is_wp_error($api_key)) {
            return $api_key;
        }

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => $tool_name,
                'arguments' => (object) $arguments,
            ],
            'id' => time(),
        ];

        $response = wp_remote_post(self::get_mcp_url(), [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json, text/event-stream',
                'X-Goog-Api-Key' => $api_key,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            $body = wp_remote_retrieve_body($response);
            return new WP_Error(
                'stitch_http_error',
                'Stitch MCP error ' . $status_code . ': ' . substr($body, 0, 300)
            );
        }

        return $this->parse_mcp_response($response);
    }

    /**
     * Parse a Stitch MCP response body that may be JSON or SSE text.
     */
    public function parse_mcp_response(array $response): array|string|WP_Error
    {
        $raw_text = wp_remote_retrieve_body($response);
        $content_type = (string) wp_remote_retrieve_header($response, 'content-type');

        if (str_contains($content_type, 'text/event-stream')) {
            return $this->parse_sse_body($raw_text);
        }

        $json = json_decode($raw_text, true);
        if (!is_array($json)) {
            return $raw_text;
        }

        $text_content = $json['result']['content'] ?? null;
        if (is_array($text_content)) {
            foreach ($text_content as $item) {
                if (($item['type'] ?? '') !== 'text' || !isset($item['text']) || !is_string($item['text'])) {
                    continue;
                }

                $decoded_text = json_decode($item['text'], true);
                return is_array($decoded_text) ? $decoded_text : $item['text'];
            }
        }

        return $json;
    }

    /**
     * Parse Stitch SSE output into either decoded data or a raw string.
     */
    public function parse_sse_body(string $raw_text): array|string
    {
        $chunks = [];

        foreach (preg_split('/\r?\n/', $raw_text) as $line) {
            if (!str_starts_with($line, 'data: ')) {
                continue;
            }

            $data = json_decode(substr($line, 6), true);
            if (!is_array($data) || empty($data['result']['content']) || !is_array($data['result']['content'])) {
                continue;
            }

            foreach ($data['result']['content'] as $item) {
                if (($item['type'] ?? '') === 'text' && isset($item['text']) && is_string($item['text'])) {
                    $chunks[] = $item['text'];
                }
            }
        }

        $joined = implode('', $chunks);
        if ($joined === '') {
            return '';
        }

        $decoded = json_decode($joined, true);
        return is_array($decoded) ? $decoded : $joined;
    }

    /**
     * List Stitch projects in the builder-friendly format.
     */
    public function list_projects(): array|WP_Error
    {
        $data = $this->call_tool('list_projects');
        if (is_wp_error($data)) {
            return $data;
        }

        $projects = $data['projects'] ?? $data;
        if (!is_array($projects)) {
            return new WP_Error('invalid_projects_response', 'Unexpected Stitch projects response.');
        }

        return array_map(static function ($project) {
            return [
                'id' => self::last_path_segment($project['name'] ?? ''),
                'name' => $project['name'] ?? '',
                'title' => $project['title'] ?? 'Untitled Project',
                'thumbnail' => $project['thumbnailScreenshot']['downloadUrl'] ?? null,
                'deviceType' => $project['deviceType'] ?? 'DESKTOP',
                'screenCount' => is_array($project['screenInstances'] ?? null) ? count($project['screenInstances']) : 0,
                'screens' => array_map(static function ($screen) {
                    return [
                        'id' => self::last_path_segment($screen['sourceScreen'] ?? '') ?: ($screen['id'] ?? ''),
                        'sourceScreen' => $screen['sourceScreen'] ?? '',
                        'label' => $screen['label'] ?? null,
                        'width' => $screen['width'] ?? 1280,
                        'height' => $screen['height'] ?? 900,
                        'hidden' => (bool) ($screen['hidden'] ?? false),
                    ];
                }, is_array($project['screenInstances'] ?? null) ? $project['screenInstances'] : []),
                'updatedAt' => $project['updateTime'] ?? null,
            ];
        }, $projects);
    }

    /**
     * List screens for a Stitch project.
     */
    public function list_screens(string $project_id): array|WP_Error
    {
        $data = $this->call_tool('list_screens', ['project_id' => $project_id]);
        if (is_wp_error($data)) {
            return $data;
        }

        $screens = $data['screens'] ?? $data;
        if (!is_array($screens)) {
            return new WP_Error('invalid_screens_response', 'Unexpected Stitch screens response.');
        }

        return array_map(static function ($screen) {
            return [
                'id' => self::last_path_segment($screen['name'] ?? ''),
                'name' => $screen['name'] ?? '',
                'title' => $screen['title'] ?? 'Untitled Screen',
                'screenshot' => $screen['screenshot']['downloadUrl'] ?? null,
                'width' => $screen['width'] ?? '1280',
                'height' => $screen['height'] ?? '900',
                'deviceType' => $screen['deviceType'] ?? 'DESKTOP',
                'hidden' => (bool) ($screen['hidden'] ?? false),
            ];
        }, $screens);
    }

    /**
     * Get a single screen's metadata.
     */
    public function get_screen(string $project_id, string $screen_id): array|WP_Error
    {
        $data = $this->call_tool('get_screen', [
            'project_id' => $project_id,
            'screen_id' => $screen_id,
        ]);

        if (is_wp_error($data)) {
            return $data;
        }

        $screen = $data['screen'] ?? $data;
        if (!is_array($screen)) {
            return new WP_Error('invalid_screen_response', 'Unexpected Stitch screen response.');
        }

        return [
            'id' => $screen_id,
            'title' => $screen['title'] ?? 'Untitled Screen',
            'screenshot' => $screen['screenshot']['downloadUrl'] ?? null,
            'htmlUrl' => $screen['htmlCode']['downloadUrl'] ?? null,
            'width' => $screen['width'] ?? '1280',
            'height' => $screen['height'] ?? '900',
            'deviceType' => $screen['deviceType'] ?? 'DESKTOP',
        ];
    }

    /**
     * Download raw screen HTML from Stitch's signed URL.
     */
    public function download_screen_html(string $signed_url): string|WP_Error
    {
        $response = wp_remote_get($signed_url, ['timeout' => 60]);
        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error('screen_html_download_failed', 'Failed to download HTML: ' . $status_code);
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * Resolve a screen's HTML payload.
     */
    public function import_screen(string $project_id, string $screen_id): array|WP_Error
    {
        $screen = $this->get_screen($project_id, $screen_id);
        if (is_wp_error($screen)) {
            return $screen;
        }

        $html_url = $screen['htmlUrl'] ?? '';
        if (!is_string($html_url) || $html_url === '') {
            return new WP_Error('missing_html_url', 'No HTML available for this screen.');
        }

        $html = $this->download_screen_html($html_url);
        if (is_wp_error($html)) {
            return $html;
        }

        return [
            'html' => $html,
            'title' => $screen['title'] ?? 'Imported Screen',
            'width' => $screen['width'] ?? '1280',
            'height' => $screen['height'] ?? '900',
            'screenshotUrl' => $screen['screenshot'] ?? null,
        ];
    }

    /**
     * Legacy prompt-based generation helper.
     */
    public function generate_freeform(string $prompt): string|WP_Error
    {
        $data = $this->call_tool('generate_freeform_stream', ['prompt' => $prompt]);
        if (is_wp_error($data)) {
            return $data;
        }

        if (is_array($data)) {
            $html = $data['html'] ?? null;
            if (is_string($html)) {
                return $html;
            }

            return new WP_Error('invalid_generate_response', 'Unexpected Stitch generation response.');
        }

        return $data;
    }

    private static function last_path_segment(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $parts = explode('/', trim($path, '/'));
        return (string) end($parts);
    }
}
