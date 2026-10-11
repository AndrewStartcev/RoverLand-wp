<?php
defined('ABSPATH') || exit;
$items = array();
foreach (roverland_get_branches() as $branch) { $items[$branch['name']] = $branch; }
if (!$items) { return; }
?>
<details class="header-branches">
  <summary aria-label="Наши техцентры"><img class="header-branches__location" src="<?php echo esc_url(roverland_asset('assets/images/icons/ui/location.svg')); ?>" width="20" height="24" alt="" aria-hidden="true"><span class="header-branches__label">Техцентры</span><img class="header-branches__arrow" src="<?php echo esc_url(roverland_asset('assets/images/icons/ui/chevron-gray.svg')); ?>" width="10" height="6" alt="" aria-hidden="true"></summary>
  <div class="header-branches__list">
    <?php foreach ($items as $branch) : ?>
    <div><strong><?php echo esc_html($branch['name']); ?></strong><a href="<?php echo esc_url('tel:' . roverland_phone_href($branch['phone'])); ?>"><?php echo esc_html($branch['phone']); ?></a><a href="<?php echo esc_url(roverland_page_url('kontakty')); ?>"><?php echo esc_html($branch['address']); ?></a></div>
    <?php endforeach; ?>
  </div>
</details>
