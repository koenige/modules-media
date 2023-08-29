<?php 

/**
 * media module
 * folder operations
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * folder operations
 *
 * @param array $params
 * @return array
 */
function mod_media_folderinfo($params) {
	$folder = implode('/', $params);
	$data = [];
	$data['files'] = mod_media_folderinfo_import_files($folder);
	$data['import_count'] = count($data['files']) ? count($data['files']) :  NULL;
	$data['import'] = ($data['import_count'] AND isset($_GET['import'])) ? true : false;
	$data['imported'] = $_GET['imported'] ?? NULL;

	$sql = 'SELECT medium_id, main_medium_id
	    FROM media
	    WHERE filename = "%s"';
	$sql = sprintf($sql, wrap_db_escape($folder));
	$medium = wrap_db_fetch($sql);
	if ($medium)
		$page['link'] = mf_media_page_links($medium['medium_id'], $medium['main_medium_id']);
	
	$page['text'] = wrap_template('folderinfo', $data);
	$page['query_strings'][] = 'import';
	$page['query_strings'][] = 'imported';
	return $page;
}

/**
 * check files and add import jobs
 *
 * @param string $folder
 * @return array
 */
function mod_media_folderinfo_import_files($folder) {
	if (!wrap_path('media_import', $folder)) return [];

	$import_folder = mf_media_import_folder($folder);
	if (!$import_folder) return [];

	$files = scandir($import_folder);
	foreach ($files as $index => $file)
		if (str_starts_with($file, '.')) unset($files[$index]);
		elseif (is_dir($import_folder.'/'.$file)) unset($files[$index]);
	if (!$files) return [];
	
	$data = [];
	foreach ($files as $file) {
		$filename = $import_folder.'/'.$file;
		$file = explode('.', $file);
		$extension = (count($file) > 1) ? array_pop($file) : '';
		$file = implode('.', $file);
		$sha1_hash = sha1_file($filename);
		$data[$sha1_hash] = [
			'filesize' => filesize($filename),
			'sha1' => $sha1_hash,
			'filename' => $file,
			'extension' => $extension
		];
	}
	if (!empty($_POST['zz_import'])) {
		$i = 0;
		foreach ($_POST as $sha1_hash => $selection) {
			if ($selection !== 'on') continue;
			if (!array_key_exists($sha1_hash, $data)) continue;
			$path = wrap_path('media_import', $folder.'/'.$sha1_hash, false);
			$success = wrap_job($path, [
				'trigger' => 1,
				'job_category_id' => wrap_category_id('jobs/media')
			]);
			if ($success) $i++;
		}
		wrap_redirect_change(sprintf('?imported=%d', $i));
	}
	return $data;
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
