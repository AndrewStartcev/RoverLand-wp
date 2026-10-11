<?php
// Run explicitly: wp eval-file wp-content/themes/rover/docs/apply-content-revision.php
if ( ! defined('WP_CLI') || ! WP_CLI ) { return; }
if ( ! function_exists('update_field') ) { WP_CLI::error('ACF is required.'); }
$root = dirname(__DIR__);
$keys = array();
$walk = function($fields) use (&$walk, &$keys) {
    foreach ($fields as $field) {
        if (!empty($field['name'])) { $keys[$field['name']] = $field['key']; }
        if (!empty($field['sub_fields'])) { $walk($field['sub_fields']); }
    }
};
foreach (glob($root . '/acf-json/*.json') as $file) {
    $group = json_decode(file_get_contents($file), true);
    $walk($group['fields'] ?? array());
}
$backup = get_option('roverland_revision_20261011_backup', array());
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
$data = json_decode(file_get_contents($root . '/data/content-revision-2026-10-11.json'), true);
$home = (int) get_option('page_on_front');
if (!$home) { WP_CLI::error('Missing front page'); }
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$old = get_field('home_services', $home, false) ?: array();
$services = array();
foreach ($data['services'] as $index => $row) {
    $row['image'] = $old[$index]['image'] ?? 0;
    if (!empty($row['source_image'])) {
        $source = 'https://roverland.ru' . $row['source_image'];
        $found = get_posts(array('post_type'=>'attachment', 'posts_per_page'=>1, 'meta_key'=>'_source_url', 'meta_value'=>$source));
        $image = $found ? $found[0]->ID : media_sideload_image($source, $home, $row['title'], 'id');
        if (is_wp_error($image)) { WP_CLI::error($image->get_error_message()); }
        $row['image'] = $image;
    }
    unset($row['source_image']);
    $services[] = $row;
}
$set($home, 'home_services', $services);
$old = get_field('home_advantages', $home, false) ?: array();
$advantages = array();
foreach ($data['advantages'] as $row) {
    foreach ($old as $item) { if ($item['title'] === $row['title']) { $row['icon'] = $item['icon']; } }
    $advantages[] = $row;
}
$set($home, 'home_advantages', $advantages);
$about = $page('kompaniya');
foreach (array('about_service_title', 'about_parts_title', 'about_service_content') as $name) {
    $value = (string) get_field($name, $about, false);
    $value = str_replace(array('обслуживанияи', 'частейи'), array('обслуживания и', 'частей и'), $value);
    if ('about_service_content' === $name && false === strpos($value, 'чип-тюнинг дизельных')) {
        $value = preg_replace('/(<li[^>]*>[^<]*подвески[^<]*<\/li>)/u', '$1<li>чип-тюнинг дизельных двигателей от Autologic и Terrafirma.</li>', $value, 1, $count);
        if (!$count) { WP_CLI::error('Cannot locate suspension list item; no content guess applied.'); }
    }
    $set($about, $name, $value);
}
$jobs = $page('kompaniya/vakansii');
$set($jobs, 'vacancies_hero_title', 'Вакансии нашей компании');
$set($jobs, 'vacancies_hero_lead', 'Компания Ровер Ленд постоянно совершенствуется и развивается. В связи с этим нашей дружной команде требуются новые сотрудники, которые хотят профессионально развиваться и работать с автомобилями Land Rover и Jaguar.');
$old = get_field('vacancies_advantages', $jobs, false) ?: array();
$copy = array(
    array('Специализация на Land Rover и Jaguar', 'Ровер Ленд специализируется на техническом обслуживании и ремонте автомобилей Land Rover и Jaguar.'),
    array('Экспертная среда', 'Работа в команде профессионалов, где ценят качество, точность и глубокое знание автомобилей.'),
    array('Профессиональное развитие', 'Мы поддерживаем профессиональный рост специалистов и предоставляем возможность работать с современным сервисным и диагностическим оборудованием.')
);
$items = array();
foreach ($copy as $i=>$row) { $items[] = array('title'=>$row[0], 'text'=>$row[1], 'icon'=>$old[$i]['icon'] ?? 0); }
$set($jobs, 'vacancies_advantages', $items);
$set($jobs, 'vacancies_list_title', 'На текущий момент открыты вакансии');
$history = $page('kompaniya/istoriya');
foreach ($data['history'] as $name=>$value) {
    $set($history, $name, $value);
}
WP_CLI::success('Content revision applied. Original fields are saved in roverland_revision_20261011_backup.');
