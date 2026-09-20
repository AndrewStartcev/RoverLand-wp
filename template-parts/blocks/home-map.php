<?php

defined( 'ABSPATH' ) || exit;

$branches = roverland_get_branches();
?>
<section class="service-map" data-yandex-map>
	<div class="service-map__canvas" data-yandex-map-canvas aria-label="Карта техцентров Rover Land"></div>
	<div class="container service-map__container">
		<div class="service-map__card">
			<h2>Наши техцентры</h2>
			<?php foreach ( array_slice( $branches, 0, 2 ) as $branch ) : ?>
				<div><strong>RoverLand <?php echo esc_html( $branch['name'] ?? '' ); ?></strong><span><?php echo esc_html( $branch['address'] ?? '' ); ?></span></div>
			<?php endforeach; ?>
			<a class="text-link" href="<?php echo esc_url( roverland_page_url( 'contacts' ) ); ?>">Посмотреть на карте</a>
		</div>
	</div>
</section>
