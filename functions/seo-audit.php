<?php
/** Technical audit, October 2026. All URLs follow the current WordPress home. */
defined('ABSPATH') || exit;

function roverland_audit_meta($post_id = 0) {
 if (!$post_id && !is_front_page() && !is_singular()) {return array();}
 static $rows;
 if (null === $rows) {
  $rows = array();
  foreach (json_decode(file_get_contents(get_template_directory().'/data/seo-audit-2026-10.json'), true) ?: array() as $row) {
   $rows[trailingslashit(wp_parse_url($row[0], PHP_URL_PATH) ?: '/')] = $row;
  }
 }
 $url = $post_id ? get_permalink($post_id) : (is_front_page() ? home_url('/') : (is_singular() ? get_permalink(get_queried_object_id()) : ''));
 return $rows[trailingslashit(wp_parse_url($url, PHP_URL_PATH) ?: '/')] ?? array();
}
function roverland_audit_h1($fallback) { $row=roverland_audit_meta(); return $row[3] ?? $fallback; }
function roverland_audit_canonical($fallback='') {
 return is_front_page() ? home_url('/') : (is_singular() ? get_permalink(get_queried_object_id()) : $fallback);
}
add_filter('rank_math/frontend/title', function($title) { $r=roverland_audit_meta(); return $r[1] ?? $title; }, 99);
add_filter('rank_math/frontend/description', function($text) { $r=roverland_audit_meta(); return $r[2] ?? $text; }, 99);
add_filter('rank_math/frontend/canonical', 'roverland_audit_canonical', 99);

function roverland_audit_image() {
 $id=get_queried_object_id();
 $image=roverland_image_url(roverland_field('service_hero_image', array(), $id));
 if (!$image) { $image=roverland_image_url(roverland_field('home_hero_image', array(), (int)get_option('page_on_front'))); }
 return $image;
}
foreach (array('title'=>1, 'description'=>2) as $property=>$column) {
 add_filter('rank_math/opengraph/facebook/og_'.$property, function($value) use ($column) { $r=roverland_audit_meta(); return $r[$column] ?? $value; },99);
}
add_filter('rank_math/opengraph/url','roverland_audit_canonical',99);
add_filter('rank_math/opengraph/type',function($v){return is_front_page() || roverland_service_page_kind(get_queried_object_id()) ? 'website' : $v;},99);
add_filter('rank_math/opengraph/facebook/og_site_name',function(){return 'РоверЛенд';},99);
add_filter('rank_math/opengraph/facebook/og_locale',function(){return 'ru_RU';},99);
add_filter('rank_math/opengraph/facebook/image',function($v){return roverland_audit_image() ?: $v;},99);

/** Search both the WP body and public ACF content; use EXISTS to avoid duplicate rows. */
add_filter('posts_search', function($search,$query) {
 if (is_admin() || !$query->is_main_query() || !$query->is_search() || !$search) {return $search;}
 global $wpdb;
 $terms=$query->get('search_terms');
 if (!$terms) {return $search;}
 $parts=array();
 foreach ($terms as $term) {
  $negative=substr($term,0,1)==='-';
  if ($negative) {$term=substr($term,1);}
  $alternatives=array($term);
  if (mb_strtolower($term)==='велар') {$alternatives[]='velar';}
  if (mb_strtolower($term)==='velar') {$alternatives[]='велар';}
  $conditions=array();
  foreach ($alternatives as $word) {
   $like='%'.$wpdb->esc_like($word).'%';
   foreach (array('post_title','post_excerpt','post_content') as $col) {$conditions[]=$wpdb->prepare("{$wpdb->posts}.$col LIKE %s",$like);}
   $conditions[]=$wpdb->prepare("EXISTS (SELECT 1 FROM {$wpdb->postmeta} audit_meta WHERE audit_meta.post_id={$wpdb->posts}.ID AND audit_meta.meta_key REGEXP '^(service_|model_|home_|about_|history_|portfolio_|promotion_)' AND audit_meta.meta_value LIKE %s)",$like);
  }
  $parts[]=($negative?'NOT ':'').'('.implode(' OR ',$conditions).')';
 }
 $sql=' AND ('.implode(' AND ',$parts).')';
 if (!is_user_logged_in()) {$sql.=" AND ({$wpdb->posts}.post_password = '')";}
 return $sql;
},20,2);

function roverland_audit_navigation($items,$parent=0,$seen=array()) {
 $nodes=array();
 foreach ($items as $item) {
  if ((int)$item->menu_item_parent!==$parent || isset($seen[$item->ID])) {continue;}
  $seen[$item->ID]=true;
  $node=array('@type'=>'SiteNavigationElement','name'=>wp_strip_all_tags($item->title),'url'=>$item->url);
  $children=roverland_audit_navigation($items,(int)$item->ID,$seen);
  if ($children) {$node['hasPart']=$children;}
  $nodes[]=$node;
 }
 return $nodes;
}
function roverland_audit_schema() {
 $home=home_url('/'); $org=$home.'#organization';
 $graph=array(array('@type'=>'Organization','@id'=>$org,'name'=>'РоверЛенд','url'=>$home,
 'description'=>'Сеть автосервисов по ремонту и техническому обслуживанию Land Rover, Range Rover и Jaguar в Москве.',
 'logo'=>array('@type'=>'ImageObject','url'=>roverland_image_url(roverland_option('site_header_logo',array()),'assets/images/content/logo-black.png'))));
 $refs=array();
 $slugs=array('Запад'=>'west','Юго-Запад'=>'southwest','Юг'=>'south','Северо-Запад'=>'northwest');
 foreach (roverland_get_branches() as $branch) {
  $name=trim($branch['name'] ?? '');
  if (!isset($slugs[$name])) {continue;}
  $id=$home.'#branch-'.$slugs[$name]; $refs[]=array('@id'=>$id);
  $digits=preg_replace('/\D/','',$branch['phone'] ?? '');
  if (strlen($digits)===11 && $digits[0]==='8') {$digits='7'.substr($digits,1);}
  $node=array('@type'=>'AutoRepair','@id'=>$id,'name'=>'РоверЛенд '.$name,'url'=>home_url('/kontakty/'),
   'parentOrganization'=>array('@id'=>$org),'address'=>array('@type'=>'PostalAddress','streetAddress'=>$branch['address'] ?? '', 'addressLocality'=>'Москва','addressCountry'=>'RU'),
   'openingHoursSpecification'=>array('@type'=>'OpeningHoursSpecification','dayOfWeek'=>array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),'opens'=>'09:00','closes'=>'21:00'));
  if (preg_match('/^7\d{10}$/',$digits)) {$node['telephone']='+'.$digits;}
  $graph[]=$node;
 }
 $graph[0]['subOrganization']=$refs;
 if (is_page('kontakty')) {$graph[]=array('@type'=>'ContactPage','@id'=>home_url('/kontakty/#webpage'),'url'=>home_url('/kontakty/'),'name'=>'Контакты автосервисов РоверЛенд','description'=>'Адреса, телефоны и режим работы автосервисов РоверЛенд в Москве.','inLanguage'=>'ru-RU','mainEntity'=>array('@id'=>$org),'about'=>$refs);}
 $menus=array();
 foreach (array_unique(array_values(get_nav_menu_locations())) as $menu_id) {
  $items=wp_get_nav_menu_items($menu_id);
  if ($items) {$menus=array_merge($menus,roverland_audit_navigation($items));}
 }
 if (!$menus && class_exists('DOMDocument')) {
  ob_start(); roverland_primary_menu_fallback(); $html=ob_get_clean();
  $dom=new DOMDocument(); $prior=libxml_use_internal_errors(true);
  $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); libxml_clear_errors(); libxml_use_internal_errors($prior);
  $xpath=new DOMXPath($dom);
  $build=function($container) use (&$build,$xpath) {
   $nodes=array();
   foreach ($xpath->query('./*',$container) as $element) {
    if ($element->nodeName==='a') {
     $nodes[]=array('@type'=>'SiteNavigationElement','name'=>trim($element->textContent),'url'=>$element->getAttribute('href'));
    } else {
     $children=$build($element);
     if ($children) {$first=array_shift($children); if ($children) {$first['hasPart']=$children;} $nodes[]=$first;}
    }
   }
   return $nodes;
  };
  $root=$dom->getElementsByTagName('ul')->item(0); if ($root) {$menus=$build($root);}
 }
 if ($menus) {$graph[]=array('@type'=>'SiteNavigationElement','@id'=>$home.'#main-navigation','name'=>'Основное меню сайта','hasPart'=>$menus);}
 if (is_singular() && !is_front_page()) {
  $crumbs=roverland_service_breadcrumb_items(get_queried_object_id()); $elements=array();
  foreach ($crumbs as $index=>$crumb) {$elements[]=array('@type'=>'ListItem','position'=>$index+1,'name'=>wp_strip_all_tags($crumb['label']),'item'=>$crumb['url'] ?? get_permalink(get_queried_object_id()));}
  $graph[]=array('@type'=>'BreadcrumbList','@id'=>get_permalink(get_queried_object_id()).'#breadcrumb','itemListElement'=>$elements);
 }
 return $graph;
}
add_filter('rank_math/json_ld', function($data) {
 foreach ($data as $key=>$node) {
  if (array_intersect((array)($node['@type'] ?? array()),array('Organization','LocalBusiness','AutoRepair','ContactPage','SiteNavigationElement','BreadcrumbList'))) {unset($data[$key]);}
 }
 foreach (roverland_audit_schema() as $i=>$node) {$data['roverland_audit_'.$i]=$node;}
 return $data;
},99);

/** Serve the complete launch-ready sitemap while keeping the staging robots block. */
add_action('template_redirect',function() {
 $path=trim(wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''),PHP_URL_PATH) ?: '', '/');
 if ($path!=='sitemap.xml') {return;}
 status_header(200); header('Content-Type: application/xml; charset=UTF-8');
 $urls=array(home_url('/')=>true);
 $posts=get_posts(array('post_type'=>array('page','post','promotion','vacancy','portfolio_item'),'post_status'=>'publish','posts_per_page'=>-1,'has_password'=>false));
 foreach ($posts as $post) {
  if (in_array('noindex',(array)get_post_meta($post->ID,'rank_math_robots',true),true)) {continue;}
  $url=get_permalink($post);
  if (!$url || wp_parse_url($url,PHP_URL_QUERY) || trim(wp_parse_url($url,PHP_URL_PATH) ?: '', '/')==='vacancies') {continue;}
  $urls[$url]=true;
 }
 echo '<?xml version="1.0" encoding="UTF-8"?>';
 echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
 foreach (array_keys($urls) as $url) {echo '<url><loc>'.esc_xml($url).'</loc></url>';}
 echo '</urlset>'; exit;
},0);

// Rank Math omits canonicals on noindex staging pages. Keep a single self URL
// without changing the staging robots policy.
add_filter('rank_math/frontend/canonical','__return_false',100);
add_action('wp',function(){remove_action('wp_head','rel_canonical');});
add_action('wp_head',function(){
 $url=roverland_audit_canonical();
 if ($url) {echo '<link rel="canonical" href="'.esc_url($url).'" />'."\n";}
},99);
