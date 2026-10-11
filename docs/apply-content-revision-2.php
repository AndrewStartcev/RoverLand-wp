<?php
require __DIR__ . '/revision-bootstrap.php';
$post = function($slug, $type) {
    $p = get_page_by_path($slug, OBJECT, $type);
    if (!$p) { WP_CLI::error('Missing ' . $type . ': ' . $slug); }
    return $p->ID;
};
$save_content = function($id, $content) use (&$backup) {
    $token = $id . ':post_content';
    if (!array_key_exists($token,$backup)) { $backup[$token] = get_post_field('post_content',$id); update_option('roverland_revision_20261011_backup',$backup,false); }
    $r = wp_update_post(array('ID'=>$id,'post_content'=>wp_slash($content)),true);
    if (is_wp_error($r)) { WP_CLI::error($r->get_error_message()); }
};
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$image = function($source,$id) {
    $found = get_posts(array('post_type'=>'attachment','posts_per_page'=>1,'meta_key'=>'_source_url','meta_value'=>$source));
    $result = $found ? $found[0]->ID : media_sideload_image($source,$id,null,'id');
    if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
    return $result;
};
$branches = $normalize(get_field('site_branches','option',false)) ?: array();
$info = array(
 'Запад'=>array('roverlandzapad','8 (925) 573-72-71','126990267000','09e1819f675bb4772d4973f1668835ced0d78908bef818108f10238ab2f3ef9a'),
 'Юго-Запад'=>array('roverland_nikul','8 (925) 771-99-30','18970455953','e2bbe3883ed17f05c5afba74cb4b534ee8c1de496d3d96f37b8b250a9998b572'),
 'Юг'=>array('roverlandservice','8 (925) 793-80-60','81223809670',''),
 'Северо-Запад'=>array('roverland_volok','8 (925) 860-19-30','229914719361','2cf70eed4b7004aa8571b85e10152ba4f51d13ece352c75246e7324d8356cc14')
);
$new = array();
foreach ($info as $name=>$item) {
    foreach ($branches as $branch) { if ($branch['name'] === $name) {
        $branch['department']='';
        $branch['telegram_url']='https://t.me/' . $item[0]; $branch['telegram_phone']=$item[1];
        $branch['hours']="Пн-Вс. с 9:00 до 21:00\nБез выходных и перерывов.";
        $branch['map_url']='https://yandex.ru/maps/org/rover_lend/' . $item[2] . '/';
        $branch['map_embed']=$item[3] ? 'https://yandex.ru/map-widget/v1/?um=constructor%3A'.$item[3].'&source=constructor' : 'https://yandex.ru/map-widget/v1/?z=12&ol=biz&oid='.$item[2];
        $branch['show_in_footer']=1; $new[]=$branch; break;
    } }
}
if (count($new)!==4) { WP_CLI::error('Expected four branches.'); }
$set('option','site_branches',$new);
$summaries=array(
 'master-priemshchik-slesarnogo-tsekha'=>'Приём автомобилей, консультирование клиентов по вопросам ремонта и технического обслуживания, сопровождение автомобиля в процессе ремонта и контроль выполненных работ.',
 'avtomekhanik'=>'Техническое обслуживание и ремонт автомобилей Land Rover и Jaguar.',
 'avtodiagnost'=>'Диагностика электронных систем автомобилей Land Rover и Jaguar, поиск неисправностей и выполнение диагностических и ремонтных работ с использованием профессионального оборудования.'
);
foreach ($summaries as $slug=>$text) {
    $id=$post($slug,'vacancy'); $set($id,'vacancy_summary',$text);
    if ('master-priemshchik-slesarnogo-tsekha'===$slug) { $set($id,'vacancy_hero_title','Мастер-приемщик слесарного цеха'); }
}
$set($page('aktsii'),'promotions_hero_lead','Специальные условия, бонусы и программы лояльности для владельцев Land Rover и Jaguar.');
$source=json_decode(file_get_contents($root.'/data/legal-and-promotions-source.json'),true);
$note='* Все сведения, включая указанные на сайте цены, носят информационный характер и не являются публичной офертой, определяемой положениями статьи 437 Гражданского кодекса Российской Федерации. Фактическая стоимость товаров и услуг может отличаться от размещённой на сайте. Подробную информацию и актуальные цены уточняйте по телефону у наших менеджеров.';
$addresses='<h2>Адреса проведения акции:</h2><ul><li>Волоколамское шоссе 79A<br>Тел.: <a href="tel:+74952680424">+7 (495) 268-04-24</a></li><li>Нагатинская улица, 16, корп. 1, стр. 5<br>Тел.: <a href="tel:+74952560468">+7 (495) 256-04-68</a></li><li>Никулинская улица, 23, корп. 4, стр. 1<br>Тел.: <a href="tel:+74950210744">+7 (495) 021-07-44</a></li><li>Новорижское шоссе, 17-й километр, 1<br>Тел.: <a href="tel:+74951324535">+7 (495) 132-45-35</a></li></ul>';
$promos=array('diskontnaya-programma'=>'discount','filtr-salona-v-podarok'=>'filter','shchetki-stekloochistiteley-v-podarok'=>'wipers','uluchshim-lyuboe-predlozhenie'=>'improve','vozvrashchaem-dengi'=>'refund');
foreach ($promos as $slug=>$type) {
    $id=$post($slug,'promotion');
    $content=(string)get_field('promotion_content',$id,false);
    if ('discount'===$type || 'filter'===$type) {
        $lead='discount'===$type ? 'Компания РоверЛенд подготовила для своих клиентов специальное предложение. Обратившись в техцентры СТО Ровер Ленд, можно стать участником дисконтной накопительной программы на работы и запчасти.' : 'При выполнении антибактериальной обработки с ионизацией по фиксированной цене 3 300 - фильтр салона идет в подарок.';
        $content=preg_replace('/<p\b[^>]*>.*?<\/p>/su','<p>'.$lead.'</p>',$content,1);
    }
    if ('discount'!==$type && false===strpos($content,'Адреса проведения акции:')) { $content.=$addresses; }
    $set($id,'promotion_content',$content);
    if ('improve'!==$type) { $set($id,'promotion_note',$note); }
    if (!empty($source[$type]['images'][0])) { $set($id,'promotion_hero_image',$image('https://roverland.ru'.$source[$type]['images'][0],$id)); }
    if ('discount'===$type) { $set($id,'promotion_summary','Обратившись в техцентры РоверЛенд, вы можете стать участником накопительной дисконтной программы на работы и запчасти.'); }
    if ('improve'===$type) { $set($id,'promotion_summary','Если у вас есть калькуляция ремонта или перечень рекомендованных работ из другого сервиса, мы готовы предложить эти же работы и запчасти по более низкой цене.'); }
}
foreach(array('privacy'=>'politika-konfidentsialnosti','cookie'=>'politika-ispolzovaniya-cookie') as $kind=>$slug) {
    $id=$page($slug); $set($id,'privacy_title',$source[$kind]['title']); $set($id,'privacy_sections',$source[$kind]['sections']);
    foreach(array('privacy_lead','privacy_note_title','privacy_note_text') as $name) { $set($id,$name,''); }
}
$id=$page('partnerstvo'); $content=(string)get_post_field('post_content',$id); $pos=strpos($content,'document.write');
if (false!==$pos) { $start=strrpos(substr($content,0,$pos),'<p'); $content=substr($content,0,false===$start?$pos:$start); $save_content($id,force_balance_tags($content)); }
$home=(int)get_option('page_on_front'); $offers=$normalize(get_field('home_offers',$home,false)) ?: array();
foreach(array('shchetki-stekloochistiteley-v-podarok'=>'Стеклоочистители в подарок','filtr-salona-v-podarok'=>'Фильтр салона в подарок') as $slug=>$title) {
    $url='/aktsii/'.$slug.'/'; if (in_array($url,array_column($offers,'url'),true)) { continue; }
    $id=$post($slug,'promotion'); $offers[]=array('title'=>$title,'text'=>'filtr-salona-v-podarok'===$slug?'Получите подарок при выполнении антибактериальной обработки.':'Получите подарок, если сумма заказа наряда превысит 45000 руб','url'=>$url,'image'=>get_field('promotion_card_image',$id,false));
}
$set($home,'home_offers',$offers);
WP_CLI::success('Contacts, careers, promotions and full legal documents applied.');
