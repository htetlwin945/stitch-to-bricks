<?php
require_once 'f:/bricks-ai/includes/class-stb-converter.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$c = new STB_Converter();
$html = '<body><header class="test">Hello Header</header></body>';
$r = $c->convert($html, 'Test');
echo json_encode($r, JSON_PRETTY_PRINT);
