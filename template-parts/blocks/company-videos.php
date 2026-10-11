<?php defined('ABSPATH') || exit; $local_videos=get_option('roverland_local_video_urls',array()); ?>
<section class="company-videos content-revision-section"><div class="container">
  <div class="section-heading section-heading--center">
    <h2 class="section-heading__title">Посмотрите как работает Rover Land</h2>
    <p class="section-heading__text">Загляните внутрь наших техцентров и узнайте, как проходит обслуживание автомобилей Land Rover</p>
  </div>
  <div class="company-videos__grid">
    <?php foreach (array('RoverLand_Volokolamka_01'=>'Ровер Ленд на Волоколамском', 'RoverLand_Nikulinskaya'=>'Ровер Ленд на Никулинской') as $file=>$label) : ?>
    <?php $video_source='https://roverland.ru/video/' . $file . '.mp4'; $video_source=$local_videos[$video_source] ?? $video_source; ?>
    <figure>
      <?php $poster='https://roverland.ru/video/' . $file . ('RoverLand_Volokolamka_01' === $file ? '_poster' : '') . '.jpg'; ?>
      <video controls playsinline preload="none" poster="<?php echo esc_url($local_videos[$poster] ?? $poster); ?>">
        <source src="<?php echo esc_url($video_source); ?>" type="video/mp4">
        <a href="<?php echo esc_url($video_source); ?>">Смотреть видео</a>
      </video>
      <figcaption><?php echo esc_html($label); ?></figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
</div></section>
