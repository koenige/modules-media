<?php 

/**
 * media module
 * batch functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get folder medium_id
 * create folder if it does not exist
 *
 * @param string $folder
 * @return int
 */
function mf_media_folder($identifier) {
	if ($medium_id = wrap_id('folders', $identifier, 'check')) return $medium_id;

	// get all folders
	$folders = explode('/', $identifier);
	$paths = [];
	$path = '';
	foreach ($folders as $folder) {
		$path = sprintf('%s%s/', $path, $folder);
		$paths[$folder] = rtrim($path, '/');
	}
	
	// check if folders exist
	$main_medium_id = '';
	foreach ($paths as $folder => $path) {
		$medium_id = wrap_id('folders', $path, 'check');
		if (!$medium_id) {
			$line = [
				'main_medium_id' => $main_medium_id,
				'title' => $folder,
				'filetype_id' => wrap_id('filetypes', 'folder'),
				'sequence' => is_numeric($folder) ? $folder : ''
			];
			$main_medium_id = $medium_id = zzform_insert('media', $line);
		}
		$main_medium_id = $medium_id;
	}
	wrap_id('folders', $identifier, 'write', $medium_id);
	return $medium_id;
}
