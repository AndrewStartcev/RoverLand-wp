<?php

defined( 'ABSPATH' ) || exit;

$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();

if ( empty( $items ) ) {
	return;
}
?>
<nav class="breadcrumbs" aria-label="Хлебные крошки">
	<?php foreach ( $items as $index => $item ) : ?>
		<?php if ( $index > 0 ) : ?><span>›</span><?php endif; ?>

		<?php if ( ! empty( $item['url'] ) ) : ?>
			<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
		<?php else : ?>
			<span<?php echo $index === array_key_last( $items ) ? ' aria-current="page"' : ''; ?>>
				<?php echo esc_html( $item['label'] ); ?>
			</span>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
