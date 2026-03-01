<?php
define('ABSPATH', true); // Mock ABSPATH to allow loading the script

function site_url()
{
    return 'http://localhost';
}
function stb_log($msg)
{
    echo "[LOG] $msg\n";
}

require 'f:\bricks-ai\includes\class-stb-converter.php';

$html = file_get_contents('f:\bricks-ai\payload-modern-boilerplate.html');
$converter = new STB_Converter();
$result = $converter->convert($html, 'Hero Section');

echo "Extracted Elements: " . count($result['content']) . "\n";
$customTags = 0;
$buttonDivs = 0;
$labelledElements = 0;

foreach ($result['content'] as $el) {
    if (isset($el['label'])) {
        $labelledElements++;
    }
    if ($el['name'] === 'div' && isset($el['settings']->tag) && $el['settings']->tag === 'custom' && isset($el['settings']->customTag)) {
        $customTags++;
    }
    if ($el['name'] === 'div' && isset($el['settings']->tag) && $el['settings']->tag === 'button') {
        $buttonDivs++;
    }
}

echo "Custom Tags (e.g. semantic divs): $customTags\n";
echo "Button Divs: $buttonDivs\n";
echo "Labelled Elements: $labelledElements\n";

file_put_contents('f:\bricks-ai\test_output.json', json_encode($result, JSON_PRETTY_PRINT));
echo "Output saved to test_output.json\n";
