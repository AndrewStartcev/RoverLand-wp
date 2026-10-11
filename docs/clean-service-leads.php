<?php
if (!defined('WP_CLI') || !WP_CLI) { exit; }
require __DIR__ . '/revision-bootstrap.php';
$set($page('servis/sluzhba-evakuatsii'), 'service_hero_lead', 'Круглосуточная эвакуация автомобилей в СТО Ровер Ленд. Без перерыва и выходных.');
$fixes = array(
    'remont/polnaya-pokraska-avto' => array('и опыт различными формами', 'и опыт работы с различными формами'),
    'servis/mojka-kuzova' => array('впечатления хозяине', 'впечатления о хозяине'),
    'servis/predprodazhnaya-proverka-avtomobilya' => array('Наш сервис предлагаем', 'Наш сервис предлагает'),
);
foreach ($fixes as $path => $replacement) {
    $id = $page($path);
    foreach (array('service_hero_lead', 'service_revision_content') as $field) {
        $value = get_field($field, $id, false);
        if (is_string($value) && strpos($value, $replacement[0]) !== false) {
            $set($id, $field, str_replace($replacement[0], $replacement[1], $value));
        }
    }
}
WP_CLI::success('Service introduction copy cleaned.');
