<?php
defined('ABSPATH') || exit;
$items = array();
foreach (roverland_get_branches() as $branch) { $items[$branch['name']] = $branch; }
if (!$items) { return; }
?>
<details class="header-branches">
  <summary aria-label="Наши техцентры"><img class="header-branches__location" src="<?php echo esc_url(roverland_asset('assets/images/icons/ui/location.svg')); ?>" width="20" height="24" alt="" aria-hidden="true"><span class="header-branches__label">Техцентры</span><img class="header-branches__arrow" src="<?php echo esc_url(roverland_asset('assets/images/icons/ui/chevron-gray.svg')); ?>" width="10" height="6" alt="" aria-hidden="true"></summary>
  <div class="header-branches__list"><button type="button" class="header-branches__close" data-branches-close aria-label="Закрыть техцентры"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5 19 19M19 5 5 19"/></svg></button>
    <?php foreach ($items as $branch) : ?>
    <div><strong><?php echo esc_html($branch['name']); ?></strong><a class="contact-row" href="<?php echo esc_url('tel:' . roverland_phone_href($branch['phone'])); ?>"><?php echo roverland_contact_icon('phone'); ?><span><?php echo esc_html($branch['phone']); ?></span></a><a class="contact-row" href="<?php echo esc_url(roverland_page_url('kontakty')); ?>"><?php echo roverland_contact_icon('location'); ?><span><?php echo esc_html($branch['address']); ?></span></a><?php if (!empty($branch['hours'])) : ?><span class="contact-row contact-hours"><?php echo roverland_contact_icon('clock'); ?><span><?php echo nl2br(esc_html($branch['hours'])); ?></span></span><?php endif; ?></div>
    <?php endforeach; ?>
  </div>
</details>
