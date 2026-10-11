<?php
defined('ABSPATH') || exit;
$items = array();
foreach (roverland_get_branches() as $branch) { $items[$branch['name']] = $branch; }
if (!$items) { return; }
?>
<details class="header-branches">
  <summary>Наши техцентры <span aria-hidden="true">⌄</span></summary>
  <div class="header-branches__list">
    <?php foreach ($items as $branch) : ?>
    <div><strong><?php echo esc_html($branch['name']); ?></strong><a href="<?php echo esc_url('tel:' . roverland_phone_href($branch['phone'])); ?>"><?php echo esc_html($branch['phone']); ?></a><a href="<?php echo esc_url(roverland_page_url('kontakty')); ?>"><?php echo esc_html($branch['address']); ?></a></div>
    <?php endforeach; ?>
  </div>
</details>
