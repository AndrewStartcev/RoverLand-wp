<?php

defined( 'ABSPATH' ) || exit;

$classes = array( 'service-map', 'contacts-map' );

if ( ! empty( $args['class'] ) ) {
	$classes[] = trim( (string) $args['class'] );
}
?>
<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-yandex-map>
	<div class="service-map__canvas" data-yandex-map-canvas aria-label="Карта техцентров Rover Land"></div>
</section>
