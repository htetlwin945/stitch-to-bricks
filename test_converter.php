<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock WordPress functions
define('ABSPATH', __DIR__);
function site_url()
{
    return 'http://localhost';
}
function stb_log($msg)
{
    echo "[LOG] $msg\n";
}
function get_bloginfo($key)
{
    return 'Test Site';
}

// Load the converter
require_once __DIR__ . '/includes/class-stb-converter.php';

$html = '
<section style="padding: 40px; background-color: #f5f5f5; display: flex; flex-direction: column; gap: 24px; align-items: center;">
    <h1 style="text-align: center; color: #333;">Welcome to Stitch</h1>
    <p style="margin-bottom: 20px;">This is a test paragraph.</p>
    <div style="display: flex; gap: 16px;">
        <a href="#" class="btn primary">Get Started</a>
        <a href="#" class="btn outline">Secondary</a>
    </div>
    <ul style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;">
        <li><img src="https://example.com/img1.jpg" alt="Img 1" /></li>
        <li><img src="https://example.com/img2.jpg" alt="Img 2" /></li>
    </ul>
</section>
';

$converter = new STB_Converter();
$result = $converter->convert($html, 'Test Section');

echo json_encode($result, JSON_PRETTY_PRINT);
