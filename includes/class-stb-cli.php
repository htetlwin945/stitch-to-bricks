<?php
/**
 * WP-CLI commands for Stitch to Bricks
 */

if (!defined('ABSPATH')) {
    exit;
}

class STB_CLI
{

    /**
     * Fetch and parse a template from Stitch MCP Proxy
     * 
     * ## EXAMPLES
     * 
     *     wp stb fetch
     */
    public function fetch($args, $assoc_args)
    {
        WP_CLI::log('Starting Stitch to Bricks fetch process...');

        $api = new STB_Stitch_API();
        $html = $api->fetch_latest_template();

        if (is_wp_error($html)) {
            WP_CLI::error('API Error: ' . $html->get_error_message());
            return;
        }

        WP_CLI::success('Successfully fetched HTML from MCP Proxy.');
        WP_CLI::log('--- HTML Preview ---');
        WP_CLI::log(substr($html, 0, 500) . '...');
        WP_CLI::log('--------------------');

        WP_CLI::log('Initializing Parser...');
        $parser = new STB_Parser();
        $elements = $parser->parse($html);

        if (empty($elements)) {
            WP_CLI::error('Parser returned no elements.');
            return;
        }

        WP_CLI::success('Successfully parsed into ' . count($elements) . ' Bricks elements.');
        WP_CLI::log('--- JSON Preview ---');
        WP_CLI::log(wp_json_encode(array_slice($elements, 0, 2), JSON_PRETTY_PRINT));
        WP_CLI::log('--------------------');
    }
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('stb', 'STB_CLI');
}
