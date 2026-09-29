</main>

<?php get_template_part( 'template-parts/common/footer' ); ?>
<?php get_template_part( 'template-parts/common/search-modal' ); ?>
<?php get_template_part( 'template-parts/common/appointment-modal' ); ?>

<div class="form-success-modal" data-form-success-modal aria-hidden="true">
	<div class="form-success-modal__backdrop" data-form-success-close></div>
	<div class="form-success-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="form-success-title">
		<button class="icon-button form-success-modal__close" type="button" aria-label="Закрыть" data-form-success-close>
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5 19 19M19 5 5 19"></path></svg>
		</button>
		<div class="form-success-modal__icon" aria-hidden="true">✓</div>
		<h2 id="form-success-title">Заявка отправлена</h2>
		<p data-form-success-message>Спасибо! Мы свяжемся с вами в ближайшее время.</p>
		<button class="button button--primary" type="button" data-form-success-close>Хорошо</button>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
