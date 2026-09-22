<?php

defined( 'ABSPATH' ) || exit;

$sections = isset( $args['sections'] ) && is_array( $args['sections'] ) ? $args['sections'] : array();

foreach ( $sections as $section ) :
	$layout = isset( $section['acf_fc_layout'] ) ? $section['acf_fc_layout'] : '';

	if ( 'text' === $layout ) :
		$image     = isset( $section['image'] ) ? $section['image'] : array();
		$image_url = roverland_image_url( $image );
		$position  = isset( $section['image_position'] ) ? $section['image_position'] : 'none';
		?>
		<section class="service-content-block service-content-block--text">
			<div class="container service-content-block__layout<?php echo 'left' === $position ? ' service-content-block__layout--reverse' : ''; ?>">
				<div class="service-content-block__copy">
					<?php if ( ! empty( $section['title'] ) ) : ?><h2><?php echo esc_html( $section['title'] ); ?></h2><?php endif; ?>
					<?php echo wp_kses_post( isset( $section['content'] ) ? $section['content'] : '' ); ?>
				</div>
				<?php if ( $image_url && 'none' !== $position ) : ?>
					<div class="service-content-block__media">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, isset( $section['title'] ) ? $section['title'] : '' ) ); ?>" loading="lazy" decoding="async">
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	elseif ( 'benefits' === $layout ) :
		$items = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();
		?>
		<section class="service-content-block service-content-block--benefits">
			<div class="container">
				<?php if ( ! empty( $section['title'] ) ) : ?><h2><?php echo esc_html( $section['title'] ); ?></h2><?php endif; ?>
				<div class="service-benefits-grid">
					<?php foreach ( $items as $item ) : ?>
						<article class="service-benefit-card">
							<?php $icon_url = roverland_image_url( isset( $item['icon'] ) ? $item['icon'] : array() ); ?>
							<?php if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" width="32" height="32" alt="" aria-hidden="true"><?php endif; ?>
							<h3><?php echo esc_html( isset( $item['title'] ) ? $item['title'] : '' ); ?></h3>
							<p><?php echo esc_html( isset( $item['text'] ) ? $item['text'] : '' ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	elseif ( 'price_table' === $layout ) :
		$groups = isset( $section['groups'] ) && is_array( $section['groups'] ) ? $section['groups'] : array();
		?>
		<section class="service-content-block service-content-block--prices">
			<div class="container">
				<?php if ( ! empty( $section['title'] ) ) : ?><h2><?php echo esc_html( $section['title'] ); ?></h2><?php endif; ?>
				<div class="service-price-table-wrap">
					<table class="service-price-table">
						<thead><tr><th>Наименование работ</th><th>Стоимость работы (₽)</th><th>Стоимость запчастей (₽)</th></tr></thead>
						<tbody>
							<?php foreach ( $groups as $group ) : ?>
								<?php if ( ! empty( $group['title'] ) ) : ?><tr class="service-price-table__group"><th colspan="3"><?php echo esc_html( $group['title'] ); ?></th></tr><?php endif; ?>
								<?php foreach ( isset( $group['rows'] ) && is_array( $group['rows'] ) ? $group['rows'] : array() as $row ) : ?>
									<tr>
										<td><?php echo esc_html( isset( $row['name'] ) ? $row['name'] : '' ); ?></td>
										<td><?php echo esc_html( isset( $row['work_price'] ) ? $row['work_price'] : '' ); ?></td>
										<td><?php echo esc_html( isset( $row['parts_price'] ) ? $row['parts_price'] : '' ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php if ( ! empty( $section['note'] ) ) : ?><p class="service-content-block__note"><?php echo esc_html( $section['note'] ); ?></p><?php endif; ?>
			</div>
		</section>
		<?php
	elseif ( 'matrix_table' === $layout ) :
		$headers = isset( $section['headers'] ) && is_array( $section['headers'] ) ? $section['headers'] : array();
		$rows    = isset( $section['rows'] ) && is_array( $section['rows'] ) ? $section['rows'] : array();
		?>
		<section class="service-content-block service-content-block--matrix">
			<div class="container">
				<?php if ( ! empty( $section['title'] ) ) : ?><h2><?php echo esc_html( $section['title'] ); ?></h2><?php endif; ?>
				<div class="service-price-table-wrap">
					<table class="service-price-table service-price-table--matrix">
						<thead>
							<tr>
								<th>Работа</th>
								<?php foreach ( $headers as $header ) : ?><th><?php echo esc_html( isset( $header['label'] ) ? $header['label'] : '' ); ?></th><?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( isset( $row['name'] ) ? $row['name'] : '' ); ?></td>
									<?php foreach ( isset( $row['cells'] ) && is_array( $row['cells'] ) ? $row['cells'] : array() as $cell ) : ?>
										<td><?php echo esc_html( isset( $cell['value'] ) ? $cell['value'] : '' ); ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php if ( ! empty( $section['note'] ) ) : ?><p class="service-content-block__note"><?php echo esc_html( $section['note'] ); ?></p><?php endif; ?>
			</div>
		</section>
		<?php
	elseif ( 'cards' === $layout ) :
		$items = isset( $section['items'] ) && is_array( $section['items'] ) ? $section['items'] : array();
		?>
		<section class="service-content-block service-content-block--cards">
			<div class="container">
				<?php if ( ! empty( $section['title'] ) ) : ?><h2><?php echo esc_html( $section['title'] ); ?></h2><?php endif; ?>
				<div class="service-related__grid">
					<?php foreach ( $items as $item ) : ?>
						<article class="service-related-card">
							<?php $icon_url = roverland_image_url( isset( $item['icon'] ) ? $item['icon'] : array() ); ?>
							<?php if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" width="30" height="30" alt="" aria-hidden="true"><?php endif; ?>
							<h3><?php echo esc_html( isset( $item['title'] ) ? $item['title'] : '' ); ?></h3>
							<?php if ( ! empty( $item['text'] ) ) : ?><p><?php echo esc_html( $item['text'] ); ?></p><?php endif; ?>
							<?php if ( ! empty( $item['url'] ) ) : ?><a class="text-link" href="<?php echo esc_url( $item['url'] ); ?>">Подробнее <span aria-hidden="true">→</span></a><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	endif;
endforeach;
