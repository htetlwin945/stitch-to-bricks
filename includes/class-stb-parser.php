<?php
/**
 * STB_Parser — Entry point for Stitch HTML → Bricks conversion.
 *
 * Delegates to STB_Converter which produces native Bricks Builder
 * elements using the Core Framework design system.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STB_Parser
{
    /**
     * Parse Stitch HTML into Bricks elements.
     *
     * Returns a flat element array (the `content` portion of the Bricks clipboard format).
     * The full clipboard format (with globalClasses) is returned by parse_to_clipboard().
     *
     * @param  string $html   Full Stitch HTML document
     * @param  string $title  Screen title
     * @return array|WP_Error Flat array of Bricks elements
     */
    public function parse_html_to_bricks(string $html, string $title = ''): array|WP_Error
    {
        if (empty(trim($html))) {
            return new WP_Error('empty_payload', 'Received empty HTML payload.');
        }

        $converter = new STB_Converter();
        $clipboard = $converter->convert($html, $title);

        // Return only the content array (flat elements)
        return $clipboard['content'] ?? [];
    }

    /**
     * Parse Stitch HTML and return the full Bricks clipboard format.
     * This includes content + globalClasses + metadata.
     *
     * @param  string $html
     * @param  string $title
     * @return array|WP_Error
     */
    public function parse_to_clipboard(string $html, string $title = ''): array|WP_Error
    {
        if (empty(trim($html))) {
            return new WP_Error('empty_payload', 'Received empty HTML payload.');
        }

        $converter = new STB_Converter();
        return $converter->convert($html, $title);
    }
}
