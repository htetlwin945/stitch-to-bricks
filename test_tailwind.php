<?php
define('ABSPATH', __DIR__ . '/');

require_once 'includes/class-stb-converter.php';

if (!function_exists('stb_log')) {
    function stb_log($m)
    {
    }
}
if (!function_exists('site_url')) {
    function site_url()
    {
        return 'http://localhost';
    }
}

$converter = new STB_Converter();

$html_payload = file_get_contents(__DIR__ . '/payload-modern-boilerplate.html');
$result = $converter->convert($html_payload, 'Test Output');
file_put_contents('f:\bricks-ai\test-output.json', json_encode($result, JSON_PRETTY_PRINT));
echo "Output written to test-output.json\n";
