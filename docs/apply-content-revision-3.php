<?php
require __DIR__ . '/revision-bootstrap.php';
$source=json_decode(file_get_contents($root.'/data/portfolio-video-source.json'),true);
foreach ($source as $legacy=>$item) {
    $id=roverland_content_migration_find_portfolio($legacy);
    if (!$id) { WP_CLI::error('Missing portfolio: '.$legacy); }
    $set($id,'portfolio_content',$item['content']);
    $set($id,'portfolio_videos',$item['videos']);
}
WP_CLI::success('Four portfolio videos and original formatted descriptions restored.');
