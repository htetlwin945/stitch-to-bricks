<?php
$data = json_decode(file_get_contents('f:\bricks-ai\payload-modern-boilerplate-bricks'), true);
$map = [];
foreach ($data as $k => $v) {
    if (is_array($v) && isset($v[0]['name'])) {
        foreach ($v as $el) {
            $name = $el['name'] ?? '';
            $tag = $el['settings']['tag'] ?? '';
            $customTag = $el['settings']['customTag'] ?? '';
            $key = $name . ($tag ? ' | tag: ' . $tag : '') . ($customTag ? ' | customTag: ' . $customTag : '');
            $map[$key] = ($map[$key] ?? 0) + 1;
        }
    }
}
print_r($map);
