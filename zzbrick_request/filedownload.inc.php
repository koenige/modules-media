<?php

/**
 * media module
 * download sets of files via folder name or tag
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * download sets of files via folder name or tag
 *
 * @param array $params URL as given by zzwrap (language, identifier parts)
 *		[0] type folder or tag
 *		[1..n] identifier
 * @param array $settings
 * @return mixed false or exit if successful
 * @todo check maximum execution time, send message if creation of archive
 *		probably exceeds max execution time
 */
function mod_media_filedownload($params, $settings) {
	if (empty($params)) return false;
	// download from main folder uses -media as string
	if ($params[0] === '-media') $params[0] = '';

	$page['query_strings'] = ['mode', 'size', 'q', 'scope'];
	// downloading different sizes than original file
	if (!empty($_GET['size']) AND in_array($_GET['size'], array_keys(wrap_setting('media_sizes'))))
		$settings['size'] = $_GET['size'];
	// flat mode, i. e. all folder names are incorporated into filenames
	if (!empty($_GET['mode'])) $settings['mode'] = $_GET['mode'];

	// get files	
	$identifier = implode('/', $params);
	$sql = 'SELECT medium_id, title, description, date, time, source
			, filename, extension, md5_hash, last_update, main_medium_id, filesize
		FROM media
		LEFT JOIN filetypes USING (filetype_id)
		WHERE filename LIKE "%s/%%"
		AND filetype_id != %d
		AND published = "yes"';
	$sql = sprintf($sql
		, $identifier
		, wrap_id('filetypes', 'folder')
	);
	$files = wrap_db_fetch($sql, 'medium_id');

	// get folders
	$folder_idfs = [];
	foreach ($files as $file_id => $file) {
		$folder = dirname($file['filename']);
		$folders = explode('/', $folder);
		while ($folders) {
			$folder_idfs[] = implode('/', $folders);
			array_pop($folders);
		}
	}
	$folder_idfs = array_unique($folder_idfs);
	$sql = 'SELECT filename, title
		FROM media
		WHERE filename IN ("%s")';
	$sql = sprintf($sql, implode('","', $folder_idfs));
	$folders = wrap_db_fetch($sql, 'filename');

	// prepare files
	$files_to_zip = [];
	foreach ($files as $file_id => $file) {
		$filename = sprintf('%s/%s%s.%s'
			, wrap_setting('media_folder'), $file['filename']
			, $settings['size'] ?? (wrap_setting('media_original_filename_extension') ? '.'.wrap_setting('media_original_filename_extension') : '')
			, $file['extension']
		);
		if (!file_exists($filename)) {
			wrap_error(sprintf('Download: File %s does not exist', $filename), E_USER_NOTICE);
			unset($files[$index]);
			continue;
		}
		$folder = dirname($file['filename']);
		$folder = explode('/', $folder);
		$my_folders = [];
		while ($folder) {
			$my_folders[] = $folders[implode('/', $folder)]['title'];
			array_pop($folder);
		}
		$folder = implode('/', array_reverse($my_folders));
		if ($folder) $folder = sprintf('%s/', $folder);
		$local_filename = sprintf('%s%s.%s', $folder, $file['title'], $file['extension']);
		if (!empty($settings['mode']) AND $settings['mode'] == 'flat')
			$local_filename = str_replace('/', '_', $local_filename);
		$files_to_zip[] = [
			'filename' => $filename,
			'local_filename' => $local_filename
		];
	}

	wrap_include_files('download', 'default');
	$page = mf_default_download_restrictions($files);
	if ($page) return $page;

	return mf_default_download_zip($files_to_zip, str_replace('/', '-', $identifier));
}
