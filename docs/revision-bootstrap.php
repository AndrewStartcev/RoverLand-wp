<?php
// Run explicitly: wp eval-file wp-content/themes/rover/docs/apply-content-revision.php
if ( ! defined('WP_CLI') || ! WP_CLI ) { return; }
if ( ! function_exists('update_field') ) { WP_CLI::error('ACF is required.'); }
$root = dirname(__DIR__);
$keys = array();
$names = array();
$walk = function($fields) use (&$walk, &$keys, &$names) {
    foreach ($fields as $field) {
        if (!empty($field['name'])) { $keys[$field['name']] = $field['key']; $names[$field['key']] = $field['name']; }
        if (!empty($field['sub_fields'])) { $walk($field['sub_fields']); }
        foreach ($field['layouts'] ?? array() as $layout) { $walk($layout['sub_fields'] ?? array()); }
    }
};
foreach (glob($root . '/acf-json/*.json') as $file) {
    $group = json_decode(file_get_contents($file), true);
    $walk($group['fields'] ?? array());
}
$backup = get_option('roverland_revision_20261011_backup', array());
$normalize = function($value) use (&$normalize, $names) {
    if (!is_array($value)) { return $value; }
    $result = array();
    foreach ($value as $key=>$item) { $result[$names[$key] ?? $key] = $normalize($item); }
    return $result;
};
$set = function($id, $name, $value) use ($keys, &$backup) {
    if (!isset($keys[$name])) { WP_CLI::error('Unknown field: ' . $name); }
    $token = $id . ':' . $name;
    if (!array_key_exists($token, $backup)) {
        $backup[$token] = get_field($name, $id, false);
        update_option('roverland_revision_20261011_backup', $backup, false);
    }
    update_field($keys[$name], $value, $id);
    WP_CLI::log($token . ' updated');
};
$page = function($path) {
    $post = get_page_by_path($path);
    if (!$post) { WP_CLI::error('Missing page: ' . $path); }
    return $post->ID;
};
