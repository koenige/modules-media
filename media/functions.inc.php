<?php 

/**
 * media module
 * Common functions inside module
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get link elements for page
 *
 * @param array $medium
 * @param array $view
 * @return array
 */
function mf_media_page_links($medium, $view) {
	$sql = 'SELECT medium_id, filename AS identifier, title
		FROM media
		WHERE %s
		ORDER BY ISNULL(/*_PREFIX_*/media.sequence), /*_PREFIX_*/media.sequence, /*_PREFIX_*/media.date, time, title ASC';
	$sql = sprintf($sql, $medium['main_medium_id'] ? sprintf('main_medium_id = %d', $medium['main_medium_id']) : 'ISNULL(main_medium_id)');
	$media = wrap_db_fetch($sql, 'medium_id');
	$media = wrap_translate($media, 'media');
	$data = wrap_get_prevnext_flat($media, $medium['medium_id'], false);

	if ($medium['main_medium_id']) {
		$sql = 'SELECT medium_id, filename AS _main_identifier, title
			FROM media
			WHERE medium_id = %d';
		$sql = sprintf($sql, $medium['main_medium_id']);
		$main_medium = wrap_db_fetch($sql, 'medium_id');
		$main_medium = wrap_translate($main_medium, 'media');
		$main_medium = reset($main_medium);
		$data['_main_identifier'] = $main_medium['_main_identifier'];
		$data['_main_title'] = $main_medium['title'];
	} else {
		$data['identifier'] = $media[$medium['medium_id']]['identifier'];
	}
	if (!empty($view['hidden_path'])) {
		foreach ($data as $key => $value) {
			if (!str_ends_with($key, 'identifier')) continue;
			if (!str_starts_with($value, $view['hidden_path'])) continue;
			$data[$key] = substr($value, strlen($view['hidden_path']) + 1);
		}
	}
	return wrap_page_links($data);
}

/**
 * check for case insenstive or not normalized folder variants
 *
 * @param string $folder
 * @return string
 */
function mf_media_import_folder($folder) {
	$import_folder = wrap_setting('media_import_folder');
	if (!$import_folder) return '';
	if (!is_dir($import_folder)) return '';
	$paths = explode('/', $folder);
	foreach ($paths as $path) {
		$path = strtolower($path);
		$files = scandir($import_folder);
		$found = false;
		foreach ($files as $file) {
			if (strtolower($file) === $path) $found = $file;
			elseif (wrap_filename($file) === $path) $found = $file;
		}
		if (!$found) return '';
		$import_folder .= '/'.$found;
	}
	return $import_folder;
}
