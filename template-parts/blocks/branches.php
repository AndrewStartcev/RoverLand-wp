<?php

defined( 'ABSPATH' ) || exit;

$branches     = roverland_get_branches();
$section_class = isset( $args['class'] ) ? trim( (string) $args['class'] ) : 'branches';
$list_only    = ! empty( $args['list_only'] );
$title        = isset( $args['title'] ) ? (string) $args['title'] : '';

if ( empty( $branches ) ) {
	return;
}
?>

<?php if ( ! $list_only ) : ?>
<section class="<?php echo esc_attr( $section_class ); ?>">
	<div class="container">
		<?php if ( $title ) : ?>
			<h2 class="branches__title"><?php echo nl2br( esc_html( $title ) ); ?></h2>
		<?php endif; ?>
<?php else : ?>
<section class="<?php echo esc_attr( $section_class ); ?>" aria-label="Техцентры Rover Land">
	<div class="container">
<?php endif; ?>

		<div class="branches__list">
			<?php foreach ( $branches as $branch_index => $branch ) : ?>
				<?php
				$name       = isset( $branch['name'] ) ? $branch['name'] : '';
				$department = isset( $branch['department'] ) ? $branch['department'] : '';
				$phone      = isset( $branch['phone'] ) ? $branch['phone'] : '';
				$address    = isset( $branch['address'] ) ? $branch['address'] : '';

				if ( ! $name ) {
					continue;
				}
				?>
				<article class="branch-card" data-branch-card
					<?php if ( ! empty( $branch['map_address'] ) ) : ?>
						data-map-address="<?php echo esc_attr( $branch['map_address'] ); ?>"
					<?php endif; ?>
				>
					<h3>
						<?php echo esc_html( $name ); ?>
						<?php if ( $department ) : ?><small>(<?php echo esc_html( $department ); ?>)</small><?php endif; ?>
					</h3>

					<?php if ( $phone ) : ?>
						<a class="contact-row branch-card__phone" href="<?php echo esc_url( 'tel:' . roverland_phone_href( $phone ) ); ?>"><?php echo roverland_contact_icon('phone'); ?><span><?php echo esc_html( $phone ); ?></span></a>
					<?php endif; ?>

					<?php if ( $address ) : ?>
						<p class="contact-row branch-card__address"><?php echo roverland_contact_icon('location'); ?><span><?php if (!empty($branch['map_url'])) : ?><a href="<?php echo esc_url($branch['map_url']); ?>"><?php echo esc_html($address); ?></a><?php else : ?><?php echo esc_html($address); ?><?php endif; ?></span></p>
					<?php endif; ?>
					<?php if (!empty($branch['hours'])) : ?><p class="contact-row branch-card__hours"><?php echo roverland_contact_icon('clock'); ?><span><?php echo nl2br(esc_html($branch['hours'])); ?></span></p><?php endif; ?>
                    <?php if ('contacts-centers' !== $section_class) : ?><button type="button" class="text-link branch-card__select" data-branch-select aria-label="Показать на карте: <?php echo esc_attr($name); ?>">Показать на карте <span aria-hidden="true">→</span></button><?php endif; ?>
                    <?php if ('contacts-centers' === $section_class) : ?>
						<?php if (!empty($branch['telegram_url'])) : ?><p class="contact-row branch-card__telegram"><?php echo roverland_contact_icon('telegram'); ?><span>Telegram: <a href="<?php echo esc_url($branch['telegram_url']); ?>"><?php echo esc_html($branch['telegram_phone'] ?? 'Написать'); ?></a></span></p><?php endif; ?>

						<?php $contact_maps = array('Запад' => '126990267000', 'Юго-Запад' => '18970455953', 'Северо-Запад' => '229914719361'); $map_embed = isset($contact_maps[$name]) ? 'https://yandex.ru/map-widget/v1/?z=12&ol=biz&oid=' . $contact_maps[$name] : ($branch['map_embed'] ?? ''); ?>
						<?php if ($map_embed) : ?><iframe class="branch-card__map" src="<?php echo esc_url($map_embed); ?>" title="Карта техцентра <?php echo esc_attr($name); ?>" loading="lazy" allowfullscreen></iframe><?php endif; ?>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
