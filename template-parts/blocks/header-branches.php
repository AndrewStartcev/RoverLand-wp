<?php
defined('ABSPATH') || exit;
$items = array();
foreach (roverland_get_branches() as $branch) { $items[$branch['name']] = $branch; }
if (!$items) { return; }
?>
<details class="header-branches">
  <summary aria-label="Наши техцентры"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 10a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg><span class="header-branches__label">Техцентры</span><span aria-hidden="true">⌄</span></summary>
  <div class="header-branches__list">
    <?php foreach ($items as $branch) : ?>
    <div><strong><?php echo esc_html($branch['name']); ?></strong><a href="<?php echo esc_url('tel:' . roverland_phone_href($branch['phone'])); ?>"><?php echo esc_html($branch['phone']); ?></a><a href="<?php echo esc_url(roverland_page_url('kontakty')); ?>"><?php echo esc_html($branch['address']); ?></a></div>
    <?php endforeach; ?>
  </div>
</details>
