<?php

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'roverland_xml_tools_register_page', 125 );

function roverland_xml_tools_register_page() {
	add_submenu_page(
		'roverland-settings',
		'XML и фиды Rover Land',
		'XML и фиды',
		'manage_options',
		'roverland-xml-tools',
		'roverland_xml_tools_render'
	);
}

function roverland_xml_tools_sitemap_url() {
	if ( defined( 'RANK_MATH_VERSION' ) ) {
		return home_url( '/sitemap_index.xml' );
	}

	return home_url( '/wp-sitemap.xml' );
}

function roverland_xml_tools_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$sitemap_url = roverland_xml_tools_sitemap_url();
	$core_url    = home_url( '/wp-sitemap.xml' );
	$yandex_url  = home_url( '/' . roverland_yandex_feed_path() );
	$offers      = function_exists( 'roverland_yandex_feed_collect_offers' )
		? roverland_yandex_feed_collect_offers()
		: array();
	?>
	<div class="wrap">
		<h1>XML и фиды Rover Land</h1>
		<p>Готовые адреса для поисковых систем и Яндекс Бизнеса.</p>

		<table class="widefat striped" style="max-width:1000px">
			<thead>
				<tr>
					<th>Файл</th>
					<th>URL</th>
					<th>Состояние</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong>Основная XML-карта сайта</strong></td>
					<td><a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $sitemap_url ); ?></a></td>
					<td><?php echo defined( 'RANK_MATH_VERSION' ) ? 'Rank Math sitemap' : 'WordPress sitemap'; ?></td>
				</tr>
				<?php if ( $sitemap_url !== $core_url ) : ?>
					<tr>
						<td>WordPress XML sitemap</td>
						<td><a href="<?php echo esc_url( $core_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $core_url ); ?></a></td>
						<td>Резервная карта сайта</td>
					</tr>
				<?php endif; ?>
				<tr>
					<td><strong>Yandex Services YML</strong></td>
					<td><a href="<?php echo esc_url( $yandex_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $yandex_url ); ?></a></td>
					<td><?php echo esc_html( sprintf( 'Позиций: %d', count( $offers ) ) ); ?></td>
				</tr>
			</tbody>
		</table>

		<div class="notice notice-info inline" style="margin-top:20px">
			<p>
				<strong>Важно:</strong> YML формируется динамически из опубликованных услуг, услуг моделей и ТО.
				После изменения ACF данные в файле обновляются сразу — отдельная генерация файла не требуется.
			</p>
		</div>
	</div>
	<?php
}

add_action(
	'template_redirect',
	static function () {
		$request_path = isset( $_SERVER['REQUEST_URI'] )
			? trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' )
			: '';

		if ( 'sitemap.xml' !== $request_path ) {
			return;
		}

		wp_safe_redirect( roverland_xml_tools_sitemap_url(), 301 );
		exit;
	},
	1
);
