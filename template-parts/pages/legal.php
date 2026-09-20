<?php

defined( 'ABSPATH' ) || exit;

$title      = roverland_field( 'privacy_title', get_the_title() );
$lead       = roverland_field( 'privacy_lead', '' );
$sections   = roverland_field( 'privacy_sections', array() );
$note_title = roverland_field( 'privacy_note_title', '' );
$note_text  = roverland_field( 'privacy_note_text', '' );
?>
<section class="text-page">
	<div class="container">
		<header class="text-page__head">
			<?php
			get_template_part(
				'template-parts/elements/breadcrumbs',
				null,
				array(
					'items' => array(
						array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
						array( 'label' => get_the_title() ),
					),
				)
			);
			?>

			<h1><?php echo nl2br( esc_html( $title ) ); ?></h1>
			<?php if ( $lead ) : ?><p class="text-page__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
		</header>

		<div class="text-page__content">
			<?php if ( is_array( $sections ) ) : ?>
				<?php foreach ( $sections as $section ) : ?>
					<section>
						<h2><?php echo nl2br( esc_html( isset( $section['title'] ) ? $section['title'] : '' ) ); ?></h2>
						<?php echo wp_kses_post( isset( $section['content'] ) ? $section['content'] : '' ); ?>
					</section>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if ( $note_title || $note_text ) : ?>
				<div class="text-page__note">
					<?php if ( $note_title ) : ?><strong><?php echo esc_html( $note_title ); ?></strong><?php endif; ?>
					<?php echo esc_html( $note_text ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
