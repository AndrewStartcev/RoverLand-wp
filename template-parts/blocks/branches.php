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
			<?php foreach ( $branches as $branch ) : ?>
				<?php
				$name       = isset( $branch['name'] ) ? $branch['name'] : '';
				$department = isset( $branch['department'] ) ? $branch['department'] : '';
				$phone      = isset( $branch['phone'] ) ? $branch['phone'] : '';
				$address    = isset( $branch['address'] ) ? $branch['address'] : '';

				if ( ! $name ) {
					continue;
				}
				?>
				<article class="branch-card"
					<?php if ( ! empty( $branch['map_address'] ) ) : ?>
						data-map-address="<?php echo esc_attr( $branch['map_address'] ); ?>"
					<?php endif; ?>
				>
					<h3>
						<?php echo esc_html( $name ); ?>
						<?php if ( $department ) : ?><small>(<?php echo esc_html( $department ); ?>)</small><?php endif; ?>
					</h3>

					<?php if ( $phone ) : ?>
						<a href="<?php echo esc_url( 'tel:' . roverland_phone_href( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					<?php endif; ?>

					<?php if ( $address ) : ?>
						<p class="branch-card__address"><?php if (!empty($branch['map_url'])) : ?><a href="<?php echo esc_url($branch['map_url']); ?>"><?php echo esc_html($address); ?></a><?php else : ?><?php echo esc_html($address); ?><?php endif; ?></p>
					<?php endif; ?>
					<?php if ('contacts-centers' === $section_class) : ?>
						<?php if (!empty($branch['telegram_url'])) : ?><p>Telegram: <a href="<?php echo esc_url($branch['telegram_url']); ?>"><?php echo esc_html($branch['telegram_phone'] ?? 'Написать'); ?></a></p><?php endif; ?>
						<?php if (!empty($branch['hours'])) : ?><p><strong>Время работы:</strong><br><?php echo nl2br(esc_html($branch['hours'])); ?></p><?php endif; ?>
						<?php if (!empty($branch['map_embed'])) : ?><iframe class="branch-card__map" src="<?php echo esc_url($branch['map_embed']); ?>" title="Карта техцентра <?php echo esc_attr($name); ?>" loading="lazy" allowfullscreen></iframe><?php endif; ?>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
