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
	$file_not_found = true;
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
		$file_not_found = false;
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

	// @todo use template!
	/*
	if (count($files) > $zz_setting['download_max_files']) {
		$page['title'] = wrap_text('Download: Archive Too Big').': <br><em>'.media_object_link($params).'</em>';
		$page['text'] = '<p class="error">'
			.sprintf(wrap_text('Sorry, but you might only download up to %s files at once.'), $zz_setting['download_max_files'])
			.'</p>'."\n".'<p>'.sprintf(wrap_text('This archive would contain %s files.'), count($files))
			.'</p>'."\n";
		$page['text'] .= '<p>'.wrap_text('We recommend that you download every single subfolder instead of this main folder.').'</p>';
		return $page;
	}
	$size = 0;
	foreach ($files as $file) $size += $file['filesize'];
	if ($size > $zz_setting['download_max_filesize']) {
		$page['title'] = wrap_text('Download: Archive Too Big').': <br><em>'.media_object_link($params).'</em>';
		$page['text'] = '<p class="error">'
			.sprintf(wrap_text('Sorry, but you might only download an archive up to %s.'), wrap_bytes($zz_setting['download_max_filesize']))
			.'</p>'
			.'<p>'.sprintf(wrap_text('The requested archive would include files of %s size.'), wrap_bytes($size))
			.'</p>'."\n";
		$page['text'] .= '<p>'.wrap_text('We recommend that you download every single subfolder instead of this main folder.').'</p>';
		return $page;
	}
	*/

	// Temporary folder, so we do not mess this ZIP with other file downloads
	ignore_user_abort(1); // make sure we can delete temporary files at the end
	$temp_folder = sprintf('%s/%s%s', wrap_setting('tmp_dir'), rand(), time());
	mkdir($temp_folder);
	$zip_file = sprintf('%s/%s.zip', $temp_folder, str_replace('/', '-', $identifier));

	if (wrap_get_setting('media_download_zip_mode') === 'shell')
		$success = mod_media_filedownload_zip_shell($zip_file, $files_to_zip, $temp_folder);
	else
		$success = mod_media_filedownload_zip_php($zip_file, $files_to_zip);
	if (!$success) {
		wrap_error(sprintf('Creation of ZIP file %s failed', $identifier), E_USER_ERROR);
		exit;
	}
	
	$file = [];
	$file['name'] = $zip_file;
	$file['cleanup'] = true; // delete file after downloading
	$file['cleanup_dir'] = $temp_folder; // remove folder after downloading
	if ($file_not_found) {
		$file['error_code'] = 503;
		$file['error_msg'] = '<p class="error">'.wrap_text('None of the files for the requested archive could be found on the server.').'</p>';
	}
	return wrap_file_send($file);
}

/**
 * Create ZIP archive from files via shell zip
 * (faster ZIP creation)
 *
 * @param string $zip_file filename
 * @param string $json_file filename of JSONL-file with list of filenames
 *		[n]['filename'] absolute path to file
 *		[n]['local_filename'] relative path for ZIP archive
 * @return bool true: everything ok, false: error
 */
function mod_media_filedownload_zip_shell($zip_file, $files_to_zip, $temp_path) {
	$filelist = [];

	// create hard links to filesystem
	mkdir($temp_path.'/ln');
	chdir($temp_path.'/ln');
	$current_folder = getcwd();
	$created = [];
	foreach ($files_to_zip as $file) {
		$return = wrap_mkdir(dirname($current_folder.'/'.$file['local_filename']));
		if (is_array($return)) $created += $return;
		link(realpath($file['filename']), $current_folder.'/'.$file['local_filename']);
		$filelist[] = $file['local_filename'];
	}

	// zip files
	// -o	make zipfile as old as latest entry
	// -0	store files (no compression)
	$command = 'zip -o -0 %s %s';
	$command = sprintf($command, $zip_file, implode(' ', $filelist));
	exec($command);
	
	// cleanup files, remove hardlinks
	foreach ($files_to_zip as $file) {
		unlink($current_folder.'/'.$file['local_filename']);
	}
	$created = array_reverse($created);
	foreach ($created as $folder) {
		rmdir($folder);
	}
	chdir($temp_path);
	rmdir($temp_path.'/ln');
	return true;
}

/**
 * Create ZIP archive from files with PHP class ZipArchive
 * (if exec() is not available)
 *
 * @param string $zip_file filename
 * @param string $json_file filename of JSONL-file with list of filenames
 *		[n]['filename'] absolute path to file
 *		[n]['local_filename'] relative path for ZIP archive
 * @return bool true: everything ok, false: error
 */
function mod_media_filedownload_zip_php($zip_file, $files_to_zip) {
	$zip = new ZipArchive;
	if ($zip->open($zip_file, ZIPARCHIVE::CREATE) !== TRUE) {
		return false;
	}
	foreach ($files_to_zip as $file) {
		$zip->addFile($file['filename'], $file['local_filename']);
		// @todo maybe check if connection_aborted() but with what as a flush?
	}
	$zip->close();
	return true;
}
