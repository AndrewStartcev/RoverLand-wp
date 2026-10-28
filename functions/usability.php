<?php
/** Usability audit t-20261027-003. */
defined('ABSPATH') || exit;

function roverland_usability_label($value, $post_id, $field) {
 if (!is_string($value) || !in_array($field['name'] ?? '', array('home_services_title','home_offers_title','common_branches_title','history_branches_title','vacancies_branches_title','value'), true)) return $value;
 return str_replace(array('50K+', '50k+', '50К+', '50к+', 'Наши возможности', 'Наши предложения', 'Специализированный сервис Ленд Ровер и Ягуар в Москве!'), array('50 000+', '50 000+', '50 000+', '50 000+', 'Услуги и возможности Rover Land', 'Акции и специальные предложения', 'Наши техцентры в Москве'), $value);
}
add_filter('acf/format_value/type=text', 'roverland_usability_label', 20, 3);
add_filter('acf/format_value/type=textarea', 'roverland_usability_label', 20, 3);

function roverland_usability_nav($callback) {
 ob_start(); call_user_func($callback); $html = ob_get_clean();
 $current = untrailingslashit(wp_parse_url(home_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/')), PHP_URL_PATH));
 $tags = new WP_HTML_Tag_Processor($html);
 while ($tags->next_tag('A')) {
  $href = $tags->get_attribute('href');
  if (!$href || wp_parse_url($href, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST) || wp_parse_url($href, PHP_URL_FRAGMENT)) continue;
  $path = untrailingslashit(wp_parse_url($href, PHP_URL_PATH));
  if ($path === $current && !is_search() && !is_404()) {
   $tags->remove_attribute('href'); $tags->set_attribute('aria-current', 'page');
   if ($tags->get_attribute('aria-haspopup')) $tags->set_attribute('tabindex', '0');
  } elseif ($path && strpos($current, $path . '/') === 0) {
   $tags->add_class('is-current-section');
  }
 }
 echo $tags->get_updated_html();
}

function roverland_contact_icon($name) {
 $paths = array('location'=>'<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>', 'phone'=>'<path d="m7 3 3 5-2 2a14 14 0 0 0 6 6l2-2 5 3-2 4C10 21 3 14 3 5Z"/>', 'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', 'mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>', 'telegram'=>'<path d="m3 11 18-7-4 17-6-6-4 3 1-6 10-6-7 9"/>');
 return '<svg class="contact-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

function roverland_usability_price_tables($html) {
 if (!class_exists('DOMDocument') || stripos($html, '<table') === false) return $html;
 $dom = new DOMDocument('1.0', 'UTF-8');
 $old = libxml_use_internal_errors(true);
 $dom->loadHTML('<?xml encoding="utf-8" ?><div id="rl-price-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
 libxml_clear_errors(); libxml_use_internal_errors($old);
 $changed = false;
 $tables = array(); foreach ($dom->getElementsByTagName('table') as $table) $tables[] = $table;
 foreach ($tables as $table) {
  $rows = array(); foreach ($table->getElementsByTagName('tr') as $row) { $parent=$row->parentNode; while ($parent && $parent->nodeName !== 'table') $parent=$parent->parentNode; if ($parent === $table && $row->parentNode->nodeName !== 'thead') $rows[] = $row; }
  $groups = array(); $current = null; $prefix = array();
  foreach ($rows as $row) {
   $cells = array(); foreach ($row->childNodes as $cell) { if (in_array($cell->nodeName, array('td','th'),true)) $cells[]=$cell; }
   $is_group = count($cells) === 1 && (int)$cells[0]->getAttribute('colspan') > 1 && trim($row->textContent);
   if ($is_group) { $groups[] = array('title'=>trim($row->textContent),'rows'=>array()); $current=count($groups)-1; }
   elseif ($current !== null) $groups[$current]['rows'][] = $row;
   else $prefix[] = $row;
  }
  if (!$groups) continue;
  $changed = true;
  $replacement = $dom->createElement('div'); $replacement->setAttribute('class','price-categories');
  $thead = $table->getElementsByTagName('thead')->item(0);
  if ($prefix) { $before=$table->cloneNode(false); $before->removeAttribute('id'); if ($thead) $before->appendChild($thead->cloneNode(true)); $body=$dom->createElement('tbody'); foreach($prefix as $row) $body->appendChild($row->cloneNode(true)); $before->appendChild($body); $replacement->appendChild($before); }
  foreach ($groups as $group) {
   $details=$dom->createElement('details'); $details->setAttribute('class','price-category');
   $summary=$dom->createElement('summary'); $summary->appendChild($dom->createTextNode($group['title'])); $details->appendChild($summary);
   $wrap=$dom->createElement('div'); $wrap->setAttribute('class','service-price-table-wrap');
   $copy=$table->cloneNode(false); $copy->removeAttribute('id');
   if ($thead) $copy->appendChild($thead->cloneNode(true));
   $body=$dom->createElement('tbody'); foreach ($group['rows'] as $row) $body->appendChild($row->cloneNode(true));
   $copy->appendChild($body); $wrap->appendChild($copy); $details->appendChild($wrap); $replacement->appendChild($details);
  }
  $table->parentNode->replaceChild($replacement,$table);
 }
 if (!$changed) return $html;
 $root=$dom->getElementById('rl-price-root'); if (!$root) return $html;
 $result=''; foreach($root->childNodes as $child) $result .= $dom->saveHTML($child); return $result;
}

/** Imported matrix tables encode category headings as named rows with empty prices. */
function roverland_usability_matrix_groups($section) {
 $categories = array('Двигатель','Трансмиссия','Подвеска','Рулевое управление','Тормозная система','Электрооборудование','Обслуживание кондиционера','Развал схождение','Развал-схождение','Кузовные работы','Диагностика автомобиля','Техническое обслуживание');
 $groups = array(array('title'=>$section['first_header'] ?? 'Наименование работ', 'rows'=>array()));
 $found = false;
 foreach ($section['rows'] ?? array() as $row) {
  $empty = true;
  foreach ($row['cells'] ?? array() as $cell) { if (trim((string)($cell['value'] ?? '')) !== '') $empty = false; }
  if ($empty && in_array(trim($row['name'] ?? ''), $categories, true)) {
   $groups[] = array('title'=>trim($row['name']), 'rows'=>array()); $found=true;
  } else $groups[count($groups)-1]['rows'][] = $row;
 }
 return $found ? array_values(array_filter($groups, static function($group) {return !empty($group['rows']);})) : array();
}
