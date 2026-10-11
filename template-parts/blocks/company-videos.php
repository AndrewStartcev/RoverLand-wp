<?php defined('ABSPATH') || exit; ?>
<section class="company-videos content-revision-section"><div class="container">
  <div class="section-heading section-heading--center">
    <h2 class="section-heading__title">Посмотрите как работает Rover Land</h2>
    <p class="section-heading__text">Загляните внутрь наших техцентров и узнайте, как проходит обслуживание автомобилей Land Rover</p>
  </div>
  <div class="company-videos__grid">
    <?php foreach (array('RoverLand_Volokolamka_01'=>'Ровер Ленд на Волоколамском', 'RoverLand_Nikulinskaya'=>'Ровер Ленд на Никулинской') as $file=>$label) : ?>
    <figure>
      <video controls playsinline preload="none" poster="<?php echo esc_url('https://roverland.ru/video/' . $file . ('RoverLand_Volokolamka_01' === $file ? '_poster' : '') . '.jpg'); ?>">
        <source src="<?php echo esc_url('https://roverland.ru/video/' . $file . '.mp4'); ?>" type="video/mp4">
        <a href="<?php echo esc_url('https://roverland.ru/video/' . $file . '.mp4'); ?>">Смотреть видео</a>
      </video>
      <figcaption><?php echo esc_html($label); ?></figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
</div></section>
