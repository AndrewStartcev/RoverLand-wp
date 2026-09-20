<?php

defined( 'ABSPATH' ) || exit;
?>
<div class="search-modal" data-search-modal aria-hidden="true">
	<div class="search-modal__backdrop" data-search-close></div>

	<div class="search-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="search-title">
		<button class="icon-button search-modal__close" type="button" aria-label="Закрыть поиск" data-search-close>
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5 19 19M19 5 5 19"></path></svg>
		</button>

		<p class="search-modal__eyebrow">Поиск по сайту</p>
		<h2 id="search-title">Что вы ищете?</h2>

		<form class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<label class="visually-hidden" for="site-search">Поисковый запрос</label>
			<input id="site-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Например, ремонт двигателя" autocomplete="off">
			<button class="button button--primary" type="submit">Найти</button>
		</form>
	</div>
</div>
