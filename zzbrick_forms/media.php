<?php 

/**
 * Zugzwang Project
 * View for 'media' in gallery or list mode, including folders
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2016 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$view = 'gallery';
$path = false;
$base_breadcrumb = false;

if (isset($brick['vars'][1])) {
	if (empty($brick['vars'][1])) {
		$path = $brick['vars'][0];
		$link = '';
		$base_breadcrumb = true;
	} elseif ($brick['vars'][1] === '-') {
		$view = 'tree';
		$path = $brick['vars'][0];
		$link = '';
	} else {
		$path = $brick['vars'][0].'/'.$brick['vars'][1];
		$link = '/'.$brick['vars'][1];
	}
	$top = false;
} elseif (!empty($brick['vars'][0])) {
	if ($brick['vars'][0] === '-') {
		$view = 'tree';
		$link = '';
	} else {
		$path = $brick['vars'][0];
		$link = '/'.$brick['vars'][0];
		$top = true;
	}
}

if ($path) {
	$sql = 'SELECT medium_id, description, filename
		FROM /*_PREFIX_*/media
		WHERE filename = "%s"';
	$sql = sprintf($sql, $path);
	$folder = wrap_db_fetch($sql);
	if (!$folder) wrap_quit(404);
	if ($view !== 'tree' AND empty($_GET['q'])) {
		$zz['where']['main_medium_id'] = $folder['medium_id'];
	}
}

$zz_conf['dont_show_title_as_breadcrumb'] = true;

require __DIR__.'/../zzbrick_tables/media.php';

if ($path AND $view === 'tree') {
	$zz['list']['hierarchy']['mother_id_field_name'] = 'main_medium_id';
	$zz['list']['hierarchy']['id'] = $folder['medium_id'];
	$zz['list']['hierarchy']['hide_top_value'] = true;
	$zz['fields'][8]['show_hierarchy_subtree'] = $folder['medium_id'];
	$zz['fields'][8]['show_hierarchy_use_top_value_instead_NULL'] = true;
	$zz['sql'] .= sprintf(' WHERE filename LIKE "%s/%%"', $folder['filename']);
} elseif (!$path AND $view !== 'tree') {
	$zz['fields'][8]['hide_in_form'] = true;
}

if (!empty($brick['local_settings']['title'])) {
	$zz['title'] = wrap_text($brick['local_settings']['title']);
	if ($base_breadcrumb) {
		$zz_conf['breadcrumbs'][] = array(
			'linktext' => $zz['title']
		);
	}
}

$variants[0]['img'] = '/_layout/media/list-ul.png';
$variants[0]['alt'] = wrap_text('Display as Gallery');
$variants[0]['title'] = wrap_text('Display as Gallery');
$variants[0]['link'] = '';

$variants[1]['img'] = '/_layout/media/list-table.png';
$variants[1]['alt'] = wrap_text('Display as Table');
$variants[1]['title'] = wrap_text('Display as Table');
$variants[1]['link'] = '-/';

if ($view === 'gallery') {
	if (!empty($zz['where']['main_medium_id'])) {
		$zz['explanation'] = markdown($folder['description']);
		$base_path = str_repeat('../', substr_count($path, '/') + 1);
		$base_link = str_repeat('../', substr_count($link, '/'));
		$variants[1]['link'] = $base_link.$variants[1]['link'];
	} else {
		if (empty($_GET['q'])) {
			$zz['sql'] .= ' WHERE ISNULL(main_medium_id)';
		} else {
			$zz['sql'] .= sprintf(' WHERE /*_PREFIX_*/media.filetype_id != %d', $zz_setting['filetype_ids']['folder']);
		}
		$base_path = '';
	}
} else {
	$variants[0]['link'] = '../';
	$variants[1]['link'] = '';
}

$zz['title'] .= mod_media_switch_links($variants);

if ($view === 'gallery') {
	$zz['fields'][14]['if'][2]['link'] = array(
		'string1' => $base_path,
		'field1' => 'filename',
		'string2' => '/'
	);

	$zz['title'] .= '<br><small>';
	if (!empty($zz['where']['main_medium_id'])) {
		if ($top) {
			$zz['title'] .= '<a href="'.$base_path.'">TOP</a> / ';
		} else {
			$folder['filename'] = substr($folder['filename'], strlen($brick['vars'][0]) + 1);
		}
		$folder['filename'] = explode('/', $folder['filename']);
		foreach ($folder['filename'] as $index => $path) {
			if (!$path) continue;
			if ($index < count($folder['filename']) - 1) {
				$url = str_repeat('../', count($folder['filename']) - $index - 1);
				$zz_conf['breadcrumbs'][] = array(
					'linktext' => $path,
					'url' => $url
				);
				$zz['title'] .= '<a href="'.$url.'">'.$path.'</a> / ';
			} else {
				$zz_conf['breadcrumbs'][] = array(
					'linktext' => '<strong>'.$path.'</strong>'
				);
				$zz['title'] .= $path;
			}
		}	
		$zz['title'] .= '</small>';
	} else {
		$zz['title'] .= 'TOP</small>';
	}
	
	$zz_conf['search_form_always'] = true;
} else {
	$zz_conf['breadcrumbs'][] = array(
		'linktext' => '<strong>'.wrap_text('Filetree').'</strong>'
	);
	// Files
	$zz['fields'][14]['path'] = array(
		'root' => $zz_setting['media_folder'], 
		'webroot' => $zz_setting['files_path'],
		'string1' => '/',
		'field1' => 'filename',
		'string2' => '.',
		'string3' => $zz_setting['media_sizes']['min']['path'],
		'string4' => '.',
		'extension' => 'thumb_extension',
		'webstring1' => '?v=',
		'webfield1' => 'version'
	);
	$zz['fields'][14]['if'][2]['default_image'] = '/_layout/media/folder-120.png';
	if (!empty($zz['fields'][14]['if'][2]['class']))
		$zz['fields'][14]['if'][2]['class'] .= ' stretch40';
	if (!empty($zz['fields'][14]['class']))
		$zz['fields'][14]['class'] .= ' stretch40';
	if (!empty($zz['fields'][14]['if'][1]['class']))
		$zz['fields'][14]['if'][1]['class'] .= ' stretch40';

	$zz['fields'][2]['unless'][2]['link'] = $zz['fields'][14]['unless'][2]['link'];

	// Sequence
	if (!empty($zz['fields'][33])) {
		$zz['fields'][33]['list_append_next'] = false;
		$zz['fields'][33]['list_suffix'] = '';
	}

	// Description
	$zz['fields'][2]['list_append_next'] = true;
	$zz['fields'][3]['hide_in_list'] = false;
	$zz['fields'][3]['list_prefix'] = '<div style="font-size: 90%;">';
	$zz['fields'][3]['list_suffix'] = '</div>';

	// Filetype
	$zz['fields'][15]['title_tab'] = 'Type';
	$zz['fields'][15]['hide_in_list'] = false;
	$zz['fields'][15]['list_append_next'] = false;

	// Filesize
	$zz['fields'][26]['hide_in_list'] = false;
	$zz['fields'][26]['list_format'] = 'wrap_bytes';
	$zz['fields'][26]['list_unit'] = '';

	$zz_conf['list_display'] = 'table';

	// Hierarchy ...

	$zz['list']['hierarchy']['mother_id_field_name'] = $zz['fields'][8]['field_name'];
	$zz['list']['hierarchy']['display_in'] = $zz['fields'][2]['field_name'];
}
