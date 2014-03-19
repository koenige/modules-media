<?php 

/**
 * Zugzwang Project
 * Output of files from protected area
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2014 Gustaf Mossakowski
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

	if (!$params) return false;
	$filename = '/'.implode('/', $params);
	$filetype = substr($filename, strrpos($filename, '.') + 1);
	$identifier = substr($filename, 1, strrpos($filename, '.') - 1);

	foreach ($zz_setting['media_sizes'] as $size) {
		if (substr($identifier, - (strlen($size['path']) + 1)) === '.'.$size['path']) {
			$identifier = substr($identifier, 0, - strlen($size['path']) - 1);
			break;
		}
	}
	$sql = 'SELECT medium_id
		FROM /*_PREFIX_*/media media
		LEFT JOIN /*_PREFIX_*/filetypes filetypes
			ON filetypes.filetype_id = media.thumb_filetype_id
		WHERE filename = "%s"
		AND filetype = "%s"';
	$sql = sprintf($sql, wrap_db_escape($identifier), wrap_db_escape($filetype));

	// Check access rights
	$file = wrap_db_fetch($sql);
	// If no public access, require login
	if (!$file) require_once $zz_setting['core'].'/auth.inc.php';

	$file['name'] = $zz_setting['media_folder'].$filename;
	// Check if file exists
	if (!file_exists($file['name'])) return false;
	$file['etag'] = md5_file($file['name']);
	return wrap_file_send($file);
}
