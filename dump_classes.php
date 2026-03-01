<?php
require_once 'wp-load.php';
$classes = get_option('bricks_global_classes', []);
echo json_encode(array_slice($classes, 0, 5), JSON_PRETTY_PRINT);
