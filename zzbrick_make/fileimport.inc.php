<?php 

/**
 * media module
 * file import
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Import a file into the media folder and create thumbnails in the background
 *
 * @param array $params (parts of path of destination folder + hash of file)
 * @return array
 */
function mod_media_make_fileimport($params) {
	if (!$params) return false;

	// is filename part an import hash?
	$sha1_hash = array_pop($params);
	if (strlen($sha1_hash) !== 40) return false;
	if (!preg_match('/^[a-z0-9]+$/', $sha1_hash)) return false;
	
	$data['folder_path'] = wrap_path('media_internal', implode('/', $params));

	// thumbnail creation in background?
	if (!empty($_GET['thumbs']) AND !empty($_GET['field'])) {
		wrap_include_files('zzform.php', 'zzform');
		$zz = zzform_include('media');
		$ops = zzform($zz);
		$data['medium_id'] = wrap_html_escape($_GET['thumbs']);
		if ($ops['result'] === 'thumbnail created') {
			$data['thumbnail_created'] = true;
		} else {
			$data['thumbnail_failed'] = true;
			wrap_error(sprintf(
				'Creation of thumbnail for medium ID %s failed. (Reason: %s)'
				, $_GET['thumbs'], json_encode($ops['error'])
			));
			$page['status'] = 503;
		}
		$page['query_strings'][] = 'thumbs';
		$page['query_strings'][] = 'field';
		$page['text'] = wrap_template('fileimport', $data);
		return $page;
	}


	// is there an import folder?
	$data['folder'] = implode('/', $params);
	$import_folder = wrap_setting('media_import_folder').'/'.$data['folder'];
	if (!is_dir($import_folder)) return false;

	// does file with this sha1_hash exist in folder?
	$files = scandir($import_folder);
	$data['file'] = false;
	foreach ($files as $file) {
		$filename = $import_folder.'/'.$file;
		if (str_starts_with($file, '.')) continue;
		if (is_dir($filename)) continue;
		$file_hash = sha1_file($filename);
		if ($file_hash !== $sha1_hash) continue;
		$data['file'] = $file;
		break;
	}
	if (!$data['file']) return false;

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// import file
		$values = [];
		$values['action'] = 'insert';
		$values['ids'] = ['main_medium_id'];
		$values['POST']['main_medium_id'] = wrap_id('folders', $data['folder']);
		// @todo set thumb_filetype_id depending on source
		$values['FILES']['field_image']['name']['original'] = $data['file'];
		$values['FILES']['field_image']['tmp_name']['original'] = $import_folder.'/'.$data['file'];
		$values['FILES']['field_image']['do_not_delete']['original'] = true;
		$ops = zzform_multi('media', $values);
		if ($ops['id']) {
			$data['import_successful'] = true;
			$page['text'] = wrap_template('fileimport', $data);
			return $page;
		}
		wrap_error(sprintf(
			'Import of file %s into folder %s failed.', $data['file'], $data['folder']
		), E_USER_WARNING);
		$data['import_failed'] = true;
		$page['status'] = 503;
	}

	$page['text'] = wrap_template('fileimport', $data);
	return $page;
}
