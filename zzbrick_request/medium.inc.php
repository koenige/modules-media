<?php 

/**
 * Zugzwang Project
 * Output of files from protected area
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2014-2015, 2017 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Output of a file below DOCUMENT ROOT
 *
 * @param array $params
 *		[0]: Type
 *		[1]...: folder
 *		[n]: filename .tn.typ
 * @global array $zz_conf
 *		'prefix'
 * @global array $zz_setting
 *		'media_folder', 'media_sizes', 'core'
 * @return void (file will be sent)
 */
function mod_media_medium($params) {
	global $zz_conf;
	global $zz_setting;
	global $zz_page;

	if (!$params) return false;
	$filename = '/'.implode('/', $params);
	$filetype = substr($filename, strrpos($filename, '.') + 1);
	// sometimes, browsers coming from search engines interpret ? wrong as %3F
	if ($redirect = strpos($filetype, '%3Fv=')) {
		$filetype = substr($filetype, 0, $redirect);
		$filename = substr($filename, 0, strpos($filename, '%3Fv='));
		$new_url = explode('%3Fv=', $zz_page['url']['full']['path']);
		$new_url = implode('?v=', $new_url);
	}
	$identifier = substr($filename, 1, strrpos($filename, '.') - 1);

	$media_sizes = $zz_setting['media_sizes'];
	$media_sizes['master'] = ['path' => 'master']; 
	foreach ($media_sizes as $size) {
		if (substr($identifier, - (strlen($size['path']) + 1)) === '.'.$size['path']) {
			$identifier = substr($identifier, 0, - strlen($size['path']) - 1);
			break;
		}
	}
	$sql = 'SELECT medium_id, IF(published = "yes", 1, NULL) AS published
		FROM /*_PREFIX_*/media media
		LEFT JOIN /*_PREFIX_*/filetypes thumb_filetypes
			ON thumb_filetypes.filetype_id = media.thumb_filetype_id
		LEFT JOIN /*_PREFIX_*/filetypes filetypes
			ON filetypes.filetype_id = media.filetype_id
		WHERE filename = "%s"
		AND (thumb_filetypes.filetype = "%s" OR filetypes.filetype = "%s")';
	$sql = sprintf($sql, wrap_db_escape($identifier), wrap_db_escape($filetype), wrap_db_escape($filetype));

	// Check access rights
	$file = wrap_db_fetch($sql);
	if (!$file) return false;
	// If no public access, require login
	if (!$file['published']) {
		require_once $zz_setting['core'].'/auth.inc.php';
		wrap_auth(1);
	}

	$file['name'] = $zz_setting['media_folder'].$filename;
	// Check if file exists
	if (!file_exists($file['name'])) return false;
	if ($redirect) {
		return brick_format('%%% redirect '.$new_url.' %%%');
	}
	$file['etag'] = md5_file($file['name']);
	if (!empty($_GET['v'])) {
		wrap_cache_header_default('Cache-Control: max-age=31536000'); // 1 year
	}
	return wrap_file_send($file);
}
