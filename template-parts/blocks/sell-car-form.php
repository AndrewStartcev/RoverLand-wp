<?php defined('ABSPATH') || exit; ?>
<section class="appointment" id="appointment"><div class="container"><h2>Отправить заявку на выкуп автомобиля</h2>
<form class="service-form sell-car-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" data-roverland-form>
<?php echo roverland_seed_form_hidden_fields('sell'); ?>
<div class="service-form__row">
<label class="field"><span class="field__label">Марка *</span><select class="field__control" name="car_make" required><option value="">Выбрать</option><option>Land Rover</option><option>Jaguar</option></select></label>
<label class="field"><span class="field__label">Модель *</span><select class="field__control" name="car_model" required><option value="">Выбрать</option><?php foreach(get_posts(array('post_type'=>'rover_model','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC')) as $model): ?><option><?php echo esc_html(get_the_title($model)); ?></option><?php endforeach; ?></select></label>
</div><div class="service-form__row">
<label class="field"><span class="field__label">Год авто</span><select class="field__control" name="car_year"><option value="">Выбрать</option><?php for($year=(int)wp_date('Y');$year>=1980;$year--): ?><option><?php echo esc_html($year); ?></option><?php endfor; ?></select></label>
<label class="field"><span class="field__label">Пробег авто, км</span><input class="field__control" name="car_mileage" type="number" min="0" placeholder="Укажите пробег"></label>
</div><div class="service-form__row">
<label class="field"><span class="field__label">Желаемая цена, ₽</span><input class="field__control" name="car_price" type="number" min="0" placeholder="Желаемая цена"></label>
<label class="field"><span class="field__label">Имя *</span><input class="field__control" name="name" autocomplete="name" required></label>
</div><label class="field"><span class="field__label">Телефон *</span><input class="field__control" type="tel" name="phone" autocomplete="tel" placeholder="+7 (___) ___-__-__" required></label>
<label class="service-form__consent"><input type="checkbox" name="consent" value="1" required><span>Нажимая на кнопку, я принимаю <a href="<?php echo esc_url(roverland_page_url('politika-konfidentsialnosti')); ?>">условия обработки персональных данных</a>.</span></label>
<button class="button button--primary" type="submit">Отправить заявку</button><p class="service-form__status" role="status" aria-live="polite" data-form-status></p></form></div></section>
