<?php 

/**
 * media module
 * View for 'media' in gallery or list mode, including folders
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require_once __DIR__.'/../zzbrick_rights/access.inc.php';

/* how to display media? */
$view['type'] = 'gallery';
$path = false;
$base_breadcrumb = false;
$suffixlink = '';

if (isset($brick['vars'][1])) {
	if (empty($brick['vars'][1])) {
		$path = $brick['vars'][0];
		$link = '';
		$base_breadcrumb = true;
	} elseif ($brick['vars'][1] === '-') {
		$view['type'] = 'tree';
		$path = $brick['vars'][0];
		$link = '';
	} else {
		$path = $brick['vars'][0].'/'.$brick['vars'][1];
		$link = '/'.$brick['vars'][1];
	}
	$top = false;
} elseif (!empty($brick['vars'][0])) {
	if (substr($brick['vars'][0], - 2) === '/-') {
		$view['type'] = 'tree';
		$path = substr($brick['vars'][0], 0, -2);
		$link = '/'.$path;
		$top = true;
		$suffixlink = '-/';
	} elseif ($brick['vars'][0] === '-') {
		$view['type'] = 'tree';
		$link = '';
	} else {
		$path = $brick['vars'][0];
		$link = '/'.$path;
		$top = true;
	}
}

if ($path) {
	$sql = 'SELECT medium_id, description, filename
			, IF(filetype_id != %d, 1, NULL) AS is_file
		FROM /*_PREFIX_*/media
		WHERE filename = "%s"';
	$sql = sprintf($sql
		, wrap_id('filetypes', 'folder')
		, $path
	);
	$folder = wrap_db_fetch($sql);
	if (!$folder) wrap_quit(404);
}

$zz_conf['dont_show_title_as_breadcrumb'] = true;

/* include media table definition */
$zz = zzform_include_table('media', $brick['local_settings']);

$zz_conf['limit'] = 42;
$zz_conf['list_display'] = 'ul';
$zz_conf['max_select'] = 100;

if (empty($brick['local_settings']['no_publish'])) {
	if (!isset($zz_conf['footer_text'])) $zz_conf['footer_text'] = '';
	$zz_conf['footer_text'] .= '<p><em>'.wrap_text('Coloured border: medium is published; gray border: medium is not published.').'</em></p>';
}

$zz['page']['head'] = "\t".'<link rel="stylesheet" type="text/css" href="'.$zz_setting['layout_path'].'/media/zzform-media.css">'."\n";

/* modify SQL query */
if ($view['type'] === 'gallery') {
	if ($path AND empty($_GET['q']))
		$zz['where']['main_medium_id'] = $folder['medium_id'];
	elseif (empty($_GET['q']))
		$zz['sql'] .= ' WHERE ISNULL(main_medium_id)';
	else
		$zz['sql'] .= sprintf(' WHERE /*_PREFIX_*/media.filetype_id != %d', wrap_filetype_id('folder'));
	if (!$path)
		$zz['fields'][8]['hide_in_form'] = true;
} elseif ($path) {
	// $view['type'] = tree
	$zz['sql'] .= sprintf(' WHERE filename LIKE "%s/%%"', $folder['filename']);

	$zz['list']['hierarchy']['mother_id_field_name'] = 'main_medium_id';
	$zz['list']['hierarchy']['id'] = $folder['medium_id'];
	$zz['list']['hierarchy']['hide_top_value'] = true;
	$zz['fields'][8]['show_hierarchy_subtree'] = $folder['medium_id'];
	$zz['fields'][8]['show_hierarchy_use_top_value_instead_NULL'] = true;
}

/* title */
if (!empty($brick['local_settings']['title'])) {
	$zz['title'] = wrap_text($brick['local_settings']['title']);
}

$variants[0]['img'] = $zz_setting['layout_path'].'/media/list-ul.png';
$variants[0]['alt'] = wrap_text('Gallery');
$variants[0]['title'] = wrap_text('Display as Gallery');
$variants[0]['link'] = '';

$variants[1]['img'] = $zz_setting['layout_path'].'/media/list-table.png';
$variants[1]['alt'] = wrap_text('Table');
$variants[1]['title'] = wrap_text('Display as Table');
$variants[1]['link'] = '-/';

if ($view['type'] === 'gallery') {
	if (!empty($zz['where']['main_medium_id'])) {
		$view['base_path'] = str_repeat('../', substr_count($path, '/') + 1);
	} else {
		if (empty($_GET['q'])) {
			$view['base_path'] = '';
		} else {
			$view['base_path'] = $path ? str_repeat('../', substr_count($path, '/') + 1) : '';
		}
	}
} else {
	$variants[0]['link'] = '../';
	$variants[1]['link'] = '';
	$view['base_path'] = str_repeat('../', substr_count($path, '/') + ($path ? 2 : 1)).'-/';
}

$zz['title'] .= mf_media_switch_links($variants);

if ($view['type'] === 'tree') {
	$zz_conf['breadcrumbs'][] = [
		'linktext' => (!$path ? '<strong>' : '').wrap_text('Filetree').(!$path ? '</strong>' : ''),
		'url' => ($path ? str_repeat('../', substr_count($path, '/')).'../../-/' : '')
	];
}

$zz['title'] .= '<br><small>';
if ($path) {
	if ($top) {
		$zz['title'] .= '<a href="'.$view['base_path'].'">TOP</a> / ';
	} else {
		$folder['filename'] = substr($folder['filename'], strlen($brick['vars'][0]) + 1);
	}
	$folder['filename'] = explode('/', $folder['filename']);
	$bcs = [];
	$bpath = '';
	foreach ($folder['filename'] as $index => $path) {
		$bpath = $bpath.($bpath ? '/' : '').$path;
		$bcs[] = wrap_db_escape($bpath);
	}
	$sql = 'SELECT filename, title FROM media WHERE filename IN ("%s")';
	$sql = sprintf($sql, implode('","', $bcs));
	$btitles = wrap_db_fetch($sql, 'filename', 'key/value');
	$bpath = '';
	foreach ($folder['filename'] as $index => $path) {
		if (!$path) continue;
		$bpath = $bpath.($bpath ? '/' : '').$path;
		if ($index < count($folder['filename']) - 1) {
			$url = str_repeat('../', count($folder['filename']) - $index - 1);
			if ($suffixlink) $url = '../'.$url.$suffixlink;
			$zz_conf['breadcrumbs'][] = [
				'linktext' => wrap_html_escape($btitles[$bpath]),
				'url' => $url
			];
			$zz['title'] .= '<a href="'.$url.'">'.wrap_html_escape($btitles[$bpath]).'</a> / ';
		} else {
			$zz_conf['breadcrumbs'][] = [
				'linktext' => '<strong>'.wrap_html_escape($btitles[$bpath]).'</strong>'
			];
			$zz['title'] .= $path;
		}
	}
	$zz['title'] .= '</small>';
} else {
	$zz['title'] .= 'TOP</small>';
}

/* explanation */
if (!empty($folder['description'])) {
	$zz['explanation'] = markdown($folder['description']);
}

/* linking of images and folders */
unset($zz['fields'][14]['unless'][2]); // no direct link on image

if ($view['type'] === 'gallery') {
	$zz['fields'][14]['link'] = [
		'string1' => $view['base_path'],
		'field1' => 'filename_link',
		'string2' => '/'
	];
	
	$zz_conf['search_form_always'] = true;
	if (!empty($zz['fields'][33])) {
		$zz['fields'][33]['hide_in_list'] = true;
	}
} else {
	$zz['fields'][14]['link'] = [
		'string1' => substr($view['base_path'], 0, -2),
		'field1' => 'filename_link',
		'string2' => '/-/'
	];

	// Files
	$zz['fields'][14]['path'] = [
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
	];
	$zz['fields'][14]['if'][2]['default_image'] = $zz_setting['layout_path'].'/media/folder-120.png';
	if (!empty($zz['fields'][14]['if'][2]['class']))
		$zz['fields'][14]['if'][2]['class'] .= ' stretch40';
	if (!empty($zz['fields'][14]['class']))
		$zz['fields'][14]['class'] .= ' stretch40';
	if (!empty($zz['fields'][14]['if'][1]['class']))
		$zz['fields'][14]['if'][1]['class'] .= ' stretch40';

	$zz['fields'][2]['link'] = $zz['fields'][14]['link'];

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

	// Pixel size
	$zz['fields'][37]['hide_in_list'] = false;
	$zz['fields'][38]['hide_in_list'] = false;

	$zz_conf['list_display'] = 'table';

	// Hierarchy ...

	$zz['list']['hierarchy']['mother_id_field_name'] = $zz['fields'][8]['field_name'];
	$zz['list']['hierarchy']['display_in'] = $zz['fields'][2]['field_name'];
}

// display image
if (!empty($folder['is_file'])) {
	$zz['list']['hide_empty_table'] = true;
	$zz['request'][] = 'mediuminfo';
	
	$zz_conf['add'] = false;
	$zz_conf['footer_text'] = false;
}
