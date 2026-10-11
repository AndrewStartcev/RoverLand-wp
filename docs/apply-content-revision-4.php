<?php
require __DIR__ . '/revision-bootstrap.php';
$id = $page('pokupka-avto-iz-korei-i-evropy');
$data = json_decode(file_get_contents($root . '/data/vehicle-import-revision.json'), true);
foreach ($data as $key=>$value) { $set($id, 'import_' . $key, $value); }
WP_CLI::success('Vehicle import content applied.');
