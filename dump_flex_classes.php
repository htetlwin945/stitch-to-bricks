<?php
require_once('/var/www/html/wp-load.php');
$classes = get_option('bricks_global_classes', []);
$tailwind = array_filter($classes, function ($c) {
    return isset($c['name']) && str_starts_with($c['name'], 'flex');
});
echo json_encode(array_values(array_slice($tailwind, 0, 5)), JSON_PRETTY_PRINT);
