<?php 

/**
 * media module
 * View for 'media' in gallery or list mode, including folders
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require_once __DIR__.'/../zzbrick_rights/access.inc.php';

/* how to display media? */
$view = mf_media_mediapool_view($brick['vars'], $brick['parameter']);
if ($view['hidden_path'])
	$brick['local_settings']['filename_cut'] = strlen($view['hidden_path']) + 2;

/* include media table definition */
$zz = zzform_include('media', $brick['local_settings']);
$zz['page']['extra']['class'] = 'mediapool';
if (!empty($view['tag_overview'])) {
	$zz['record']['add'] = false;
	$zz['list']['display'] = false;
	$zz['page']['request'] = 'mediatags';
	return;
}

$zz['list']['display'] = 'ul';
$zz['setting']['zzform_limit'] = 42;
$zz['setting']['zzform_max_select'] = 100;

if (empty($brick['local_settings']['no_publish'])) {
	if (!isset($zz['footer']['text'])) $zz['footer']['text'] = '';
	$zz['footer']['text'] .= '<p><em>'.wrap_text('Coloured border: medium is published; gray border: medium is not published.').'</em></p>';
}

$zz['page']['extra']['css'][] = 'media/zzform-media.css';

/* get information about folder if it is not top level */
$folder = mf_media_mediapool_folder($view);

/* modify SQL query */
if (!empty($view['tag'])) {
	$zz['sql'] = wrap_edit_sql($zz['sql'], 'JOIN', 'LEFT JOIN /*_PREFIX_*/media_categories USING (medium_id)');
	$zz['sql'] = wrap_edit_sql($zz['sql'], 'WHERE', sprintf('/*_PREFIX_*/media_categories.category_id = %d', $view['category_id']));
}
if ($view['type'] === 'gallery') {
	if ($folder AND empty($_GET['q']))
		$zz['where']['main_medium_id'] = $folder['medium_id'];
	elseif (empty($_GET['q']) AND empty($view['tag']))
		$zz['sql'] = wrap_edit_sql($zz['sql'], 'WHERE', 'ISNULL(main_medium_id)');
	else
		$zz['sql'] = wrap_edit_sql($zz['sql'], sprintf('/*_PREFIX_*/media.filetype_id != %d', wrap_filetype_id('folder')));
	if (!$folder)
		$zz['fields'][8]['hide_in_form'] = true;
} elseif ($folder) {
	// $view['type'] = tree
	$zz['sql'] = wrap_edit_sql($zz['sql'], 'WHERE', sprintf('filename LIKE "%s/%%"', $folder['filename']));

	$zz['list']['hierarchy']['mother_id_field_name'] = 'main_medium_id';
	$zz['list']['hierarchy']['id'] = $folder['medium_id'];
	$zz['list']['hierarchy']['hide_top_value'] = true;
	$zz['fields'][8]['show_hierarchy_subtree'] = $folder['medium_id'];
	$zz['fields'][8]['show_hierarchy_use_top_value_instead_NULL'] = true;
}

/* title */
if (!empty($brick['local_settings']['title']))
	$zz['title'] = wrap_text($brick['local_settings']['title']);
$zz['vars']['title'] = $zz['title'];
if (!empty($folder['titles'])) {
	$title_paths = [];
	foreach ($folder['titles'] as $title)
		$title_paths[] = $title['title'];
	if ($title_paths)
		$zz['title'] = sprintf('%s: %s', $zz['title'], implode(' / ', $title_paths));
}

/* breadcrumbs */
$zz['page']['dont_show_title_as_breadcrumb'] = true;
if ($folder) {
	foreach ($folder['breadcrumbs'] as $index => $folder_path) {
		if ($index < count($folder['breadcrumbs']) - 1) {
			$zz['page']['breadcrumbs'][] = [
				'title' => wrap_html_escape($folder_path['title']),
				'url_path' => $folder_path['url']
			];
		} else {
			$zz['page']['breadcrumbs'][] = [
				'title' => wrap_html_escape($folder_path['title'])
			];
		}
	}
}

/* explanation */
if (!empty($folder['description']) AND empty($folder['is_file'])) {
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
	
	$zz['setting']['zzform_search_form_always'] = true;
	if (!empty($zz['fields'][33])) {
		$zz['fields'][33]['hide_in_list'] = true;
	}
} else {
	$zz['fields'][14]['link'] = [
		'string1' => $view['base_path'],
		'field1' => 'filename_link',
		'string2' => '/-/'
	];
	$zz['fields'][14]['if'][2]['default_image'] = wrap_setting('layout_path').'/media/folder-120.png';
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

	$zz['list']['display'] = 'table';

	// Hierarchy ...

	$zz['list']['hierarchy']['mother_id_field_name'] = $zz['fields'][8]['field_name'];
	$zz['list']['hierarchy']['display_in'] = $zz['fields'][2]['field_name'];
}

// display image
if (!empty($folder['is_file'])) {
	$zz['list']['hide_empty_table'] = true;
	$zz['page']['request'][] = 'mediuminfo';
	
	$zz['record']['add'] = false;
	$zz['footer']['text'] = false;
} else {
	$zz['page']['request'][] = 'folderinfo';
}
$zz['vars']['view'] = $view;
$zz['vars']['folder'] = $folder;

$zz['record']['redirect_to_referer_zero_records'] = true;
$zz['page']['dynamic_referer'] = $zz['fields'][14]['link'];

// filter
if (!empty($view['tag'])) {
	$zz['filter'][1]['title'] = wrap_text('Folder');
	$zz['filter'][1]['identifier'] = 'folder';
	$zz['filter'][1]['type'] = 'list';
	$zz['filter'][1]['where'] = 'main_medium_id';
	$zz['filter'][1]['sql'] = 'SELECT DISTINCT folders.medium_id, folders.filename
		FROM /*_PREFIX_*/media folders
		LEFT JOIN /*_PREFIX_*/media media
			ON media.main_medium_id = folders.medium_id
		LEFT JOIN /*_PREFIX_*/media_categories
			ON /*_PREFIX_*/media_categories.medium_id = media.medium_id
		WHERE /*_PREFIX_*/media_categories.category_id = %d';
	$zz['filter'][1]['sql'] = sprintf($zz['filter'][1]['sql'], $view['category_id']);
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
	global $zz_page;

	// get base path for media folders
	$view['base_path'] = $zz_page['db']['identifier'];
	$view['base_path'] = rtrim($view['base_path'], '*');
	$view['base_path'] = sprintf('%s/', $view['base_path']);
	
	// get full path
	$full_path = implode('/', $vars);
	$full_path_parts = $full_path ? explode('/', $full_path) : [];
	// hidden path is 'vars' minus *-'parameter' (if there are some)
	$view['hidden_path'] = $parameter ? substr($full_path, 0, -strlen($parameter) - 1) : $full_path;
	$view['hidden_path_parts'] = $view['hidden_path'] ? explode('/', $view['hidden_path']) : [];
	if (end($full_path_parts) === '-') {
		$view['type'] = 'tree';
		array_pop($full_path_parts);
	} else {
		$view['type'] = 'gallery';
	}
	if (reset($full_path_parts) === '-tags') {
		if (count($full_path_parts) === 1) {
			$view['tag_overview'] = 1;
			return $view;
		}
		$view['tag'] = array_pop($full_path_parts);
		$view['category_id'] = wrap_category_id('tags/'.$view['tag']);
		if (!$view['category_id']) wrap_quit(404);
		$view['full_path'] = NULL;
		$view['tag_path'] = '-tags/'.$view['tag'];
	} else {
		$view['full_path'] = $full_path_parts ? implode('/', $full_path_parts) : NULL;
		$view['tag_path'] = NULL;
	}
	
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
			, IF(filetype_id != /*_ID filetypes folder _*/, 1, NULL) AS is_file
		FROM /*_PREFIX_*/media
		WHERE filename = "%s"';
	$sql = sprintf($sql, $view['full_path']);
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
	$breadcrumb = '';
	foreach ($folder_paths as $index => $folder_path) {
		if (!$folder_path) continue;
		$bpath = $bpath.($bpath ? '/' : '').$folder_path;
		$breadcrumb .= $folder_path.'/';
		$folder['breadcrumbs'][] = [
			'url' => mf_media_path($view, $breadcrumb),
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
 * @param array $folder
 * @param array $view
 * @return string
 */
function mf_media_mediapool_title($title, $folder, $view) {
	$title = sprintf('<span>%s %s</span>', wrap_setting('zzform_heading_prefix'), $title);
	if (!empty($view['filecount']))
		$title .= mf_media_tools($view);
	if (wrap_setting('media_tags') AND wrap_category_id('tags', 'list') > 2) {
		$title .= sprintf('<span class="tools"><a href="%s">%s</a></span>'
			, mf_media_path($view, '-tags'), wrap_text('Tags')
		);
	}
	$title .= '<br><small>';
	if (!$folder AND empty($view['tag'])) {
		$title .= wrap_text('TOP').'</small>';
		return $title;
	}
	if (!$view['hidden_path'])
		$title .= sprintf(
			'<a href="%s">%s</a> / ', mf_media_path($view), wrap_text('TOP')
		);
	if (!empty($view['tag'])) {
		$title .= sprintf(
			'<a href="%s">%s</a> / ', mf_media_path($view, '-tags'), wrap_text('Tags')
		);
		$sql = 'SELECT category_id, category FROM categories WHERE category_id = %d';
		$sql = sprintf($sql, $view['category_id']);
		$category = wrap_db_fetch($sql);
		$category = wrap_translate($category, 'categories');
		$title .= $category['category'].'</small>';
		return $title;
	}
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

/**
 * get relative path to media folder or tag list
 *
 * @param array $view
 * @param string $path
 * @return string
 */
function mf_media_path($view, $path = '') {
	if ($path) {
		if (!empty($view['hidden_path']) AND str_starts_with($path, $view['hidden_path']))
			$path = substr($path, strlen($view['hidden_path']));
		if (str_starts_with($path, '/')) $path = substr($path, 1);
		if ($path AND !str_ends_with($path, '/')) $path = sprintf('%s/', $path);
	}
	$tree = $view['type'] === 'tree' ? '-/' : '';
	$path = sprintf('%s%s%s', $view['base_path'], $path, $tree);
	return $path;
}

/**
 * tools for media form
 *
 * @param array $view
 * @return string
 */
function mf_media_tools($view) {
	// define tools
	$tools[] = [
		'img' => 'list-ul',
		'alt' => 'Gallery',
		'title' => 'Display as Gallery',
		'link' => $view['type'] === 'tree' ? '../' : ''
	];
	$tools[] = [
		'img' => 'list-table',
		'alt' => 'Table',
		'title' => 'Display as Table',
		'link' => $view['type'] === 'tree' ? '' : '-/'
	];
	if ($path = wrap_path('media_download', $view['tag_path'] ?? $view['full_path'] ?? '-media')) {
		if (!empty($_GET['q']))
			$path = sprintf('%s?q=%s&amp;scope=%s', $path, $_GET['q'], $_GET['scope'] ?? '');
		$tools[] = [
			'img' => 'download',
			'alt' => 'Download',
			'title' => 'Download files in ZIP archive',
			'link' => $path
		];
	}

	// output tools
	return wrap_template('media-tools', $tools);
}
