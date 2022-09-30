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
$view = mf_media_mediapool_view($brick['vars'], $brick['parameter']);
if ($view['hidden_path'])
	$brick['local_settings']['filename_cut'] = strlen($view['hidden_path']) + 2;

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

/* get information about folder if it is not top level */
$folder = mf_media_mediapool_folder($view);

/* modify SQL query */
if ($view['type'] === 'gallery') {
	if ($folder AND empty($_GET['q']))
		$zz['where']['main_medium_id'] = $folder['medium_id'];
	elseif (empty($_GET['q']))
		$zz['sql'] .= ' WHERE ISNULL(main_medium_id)';
	else
		$zz['sql'] .= sprintf(' WHERE /*_PREFIX_*/media.filetype_id != %d', wrap_filetype_id('folder'));
	if (!$folder)
		$zz['fields'][8]['hide_in_form'] = true;
} elseif ($folder) {
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
$zz['title'] = mf_media_mediapool_title($zz['title'], $folder, $view);

/* breadcrumbs */
$zz_conf['dont_show_title_as_breadcrumb'] = true;
if ($folder) {
	foreach ($folder['breadcrumbs'] as $index => $folder_path) {
		if ($index < count($folder['breadcrumbs']) - 1) {
			$zz_conf['breadcrumbs'][] = [
				'linktext' => wrap_html_escape($folder_path['title']),
				'url' => $folder_path['url']
			];
		} else {
			$zz_conf['breadcrumbs'][] = [
				'linktext' => '<strong>'.wrap_html_escape($folder_path['title']).'</strong>'
			];
		}
	}
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
		'string1' => $view['base_path'],
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

/**
 * check which folder/file is chosen and how to display it
 *
 * 2 params: show only a part of the filetree, starting with sub folder
 * 1 param: show sub folder
 * 0 params: show top level folders
 *
 * @param array $params
 * @return array
 */
function mf_media_mediapool_view($vars, $parameter) {
	$full_path = implode('/', $vars);
	$full_path_parts = $full_path ? explode('/', $full_path) : [];
	// hidden path is 'vars' minus *-'parameter' (if there are some)
	$view['hidden_path'] = $parameter ? substr($full_path, 0, -strlen($parameter) - 1) : $full_path;
	$view['hidden_path_parts'] = $view['hidden_path'] ? explode('/', $view['hidden_path']) : [];
	$view['base_path'] = str_repeat('../', count($full_path_parts) - count($view['hidden_path_parts']));
	if (end($full_path_parts) === '-') {
		$view['type'] = 'tree';
		array_pop($full_path_parts);
	} else {
		$view['type'] = 'gallery';
	}
	$view['full_path'] = implode('/', $full_path_parts);
	return $view;
}

/**
 * get information about current folder (if it is not top level)
 *
 * @param array $view
 * @return array
 */
function mf_media_mediapool_folder($view) {
	if (!$view['full_path']) return [];

	$sql = 'SELECT medium_id, description, filename
			, IF(filetype_id != %d, 1, NULL) AS is_file
		FROM /*_PREFIX_*/media
		WHERE filename = "%s"';
	$sql = sprintf($sql
		, wrap_id('filetypes', 'folder')
		, $view['full_path']
	);
	$folder = wrap_db_fetch($sql);
	if (!$folder) wrap_quit(404);

	$folder_paths = explode('/', $folder['filename']);

	$filenames = [];
	$bpath = '';
	foreach ($folder_paths as $folder_path) {
		$bpath = $bpath.($bpath ? '/' : '').$folder_path;
		$filenames[] = wrap_db_escape($bpath);
	}
	$sql = 'SELECT filename, title FROM media WHERE filename IN ("%s")';
	$sql = sprintf($sql, implode('","', $filenames));
	$titles = wrap_db_fetch($sql, 'filename', 'key/value');

	$folder['breadcrumbs'] = [];
	$bpath = '';
	foreach ($folder_paths as $index => $folder_path) {
		if (!$folder_path) continue;
		$bpath = $bpath.($bpath ? '/' : '').$folder_path;
		$url = str_repeat('../', count($folder_paths) - $index - 1);
		if ($view['type'] === 'tree') $url = '../'.$url.'-/';
		$folder['breadcrumbs'][] = [
			'url' => $url,
			'title' => $titles[$bpath],
			'folder_path' => $folder_path
		];
	}
	$folder['titles'] = $folder['breadcrumbs'];
	// breadcrumbs: remove hidden folders
	foreach (array_keys($view['hidden_path_parts']) as $index) {
		unset($folder['breadcrumbs'][$index]);
		if ($index + 1 < count($view['hidden_path_parts']))
			unset($folder['titles'][$index]);
	}
	$folder['breadcrumbs'] = array_values($folder['breadcrumbs']); // get indices right
	$folder['titles'] = array_values($folder['titles']); // get indices right
	return $folder;
}

/**
 * create title for mediapool with breadcrumbs
 *
 * @param string $title
 * @param string $folder
 * @param array $view
 * @return string
 */
function mf_media_mediapool_title($title, $folder, $view) {
	$variants[0]['img'] = wrap_get_setting('layout_path').'/media/list-ul.png';
	$variants[0]['alt'] = wrap_text('Gallery');
	$variants[0]['title'] = wrap_text('Display as Gallery');
	$variants[0]['link'] = '';

	$variants[1]['img'] = wrap_get_setting('layout_path').'/media/list-table.png';
	$variants[1]['alt'] = wrap_text('Table');
	$variants[1]['title'] = wrap_text('Display as Table');
	$variants[1]['link'] = '-/';
	
	if ($view['type'] === 'tree') {
		$variants[0]['link'] = '../';
		$variants[1]['link'] = '';
	}
	$title .= mf_media_switch_links($variants);
	$title .= '<br><small>';
	if (!$folder) {
		$title .= 'TOP</small>';
		return $title;
	}
	if (!$view['hidden_path'])
		$title .= '<a href="'.$view['base_path'].'">TOP</a> / ';
	foreach ($folder['titles'] as $index => $crumb) {
		if ($index < count($folder['titles']) - 1) {
			$title .= sprintf('<a href="%s">%s</a> / '
				, $crumb['url'], wrap_html_escape($crumb['title'])
			);
		} elseif ($folder['is_file']) {
			$title .= $crumb['folder_path'];
		} else {
			$title .= wrap_html_escape($crumb['title']);
		}
	}
	$title .= '</small>';
	return $title;
}
