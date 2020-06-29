<?php 

/**
 * Zugzwang Project
 * Output of files from protected area
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2014-2015, 2017-2020 Gustaf Mossakowski
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
	$filename = $identifier = implode('/', $params);
	$identifier = explode('.', $identifier);
	$extension = count($identifier) > 1 ? array_pop($identifier) : '';
	$identifier = implode('.', $identifier);
	// sometimes, browsers coming from search engines interpret ? wrong as %3F
	if ($redirect = strpos($extension, '%3Fv=')) {
		$extension = substr($extension, 0, $redirect);
		$filename = substr($filename, 0, strpos($filename, '%3Fv='));
		$new_url = explode('%3Fv=', $zz_page['url']['full']['path']);
		$new_url = implode('?v=', $new_url);
	}

	$media_sizes = $zz_setting['media_sizes'];
	$media_sizes['master'] = ['path' => 'master'];
	$media_size = '';
	foreach ($media_sizes as $size) {
		if (substr($identifier, - (strlen($size['path']) + 1)) === '.'.$size['path']) {
			$identifier = substr($identifier, 0, - strlen($size['path']) - 1);
			$media_size = $size['path'];
			break;
		}
	}
	$variants = wrap_get_setting('media_file_variants');
	if (!empty($variants)) {
		foreach ($variants as $variant) {
			if (substr($identifier, - (strlen($variant) + 1)) === '-'.$variant) {
				$identifier = substr($identifier, 0, - strlen($variant) - 1);
				break;
			}
		}
	}
	$sql = 'SELECT medium_id, IF(published = "yes", 1, NULL) AS published
			, title AS send_as
			, description
			, filetypes.filetype
			, filetypes.filetype_description
		FROM /*_PREFIX_*/media media
		LEFT JOIN /*_PREFIX_*/filetypes thumb_filetypes
			ON thumb_filetypes.filetype_id = media.thumb_filetype_id
		LEFT JOIN /*_PREFIX_*/filetypes filetypes
			ON filetypes.filetype_id = media.filetype_id
		WHERE filename = "%s"
		AND (thumb_filetypes.extension = "%s" OR filetypes.extension = "%s")';
	$sql = sprintf($sql, wrap_db_escape($identifier), wrap_db_escape($extension), wrap_db_escape($extension));

	// Check access rights
	$file = wrap_db_fetch($sql);
	if (!$file) return false;
	// If no public access, require login
	if (!$file['published']) {
		require_once $zz_setting['core'].'/auth.inc.php';
		wrap_auth(1);
	}

	// is it an embedded medium?
	if (!empty($zz_setting['embed'])) {
		$embeds = [];
		foreach ($zz_setting['embed'] as $key => $value)
			$embeds[strtolower($key)] = $value;
		if (array_key_exists($file['filetype'], $embeds)) {
			$code = explode('/', $identifier);
			$code = array_pop($code);
			$url = sprintf($embeds[$file['filetype']], $code);
			if (!empty($_GET['inactive'])) {
				$file['url'] = $url;
				$file['privacy_policy_url'] = $zz_setting['privacy_policy_url'];
				$page['query_strings'] = ['inactive'];
				$page['title'] = $file['filetype_description'].': '.$file['send_as'];
				$page['template'] = 'embed';
				$page['url_ending'] = 'none';
				$page['text'] = wrap_template('embed', $file);
				return $page;
			} else {
				return brick_format('%%% redirect '.$url.' %%%');
			}
		}
	}
	$file['name'] = $zz_setting['media_folder'].'/'.$filename;
	// Check if file exists
	if (!file_exists($file['name'])) {
		if ($media_size) return false;
		$pos = strrpos($file['name'], '.');
		$file['name'] = substr($file['name'], 0, $pos).'.master'.substr($file['name'], $pos);
		if (!file_exists($file['name'])) return false;
	}
	if ($redirect) {
		return brick_format('%%% redirect '.$new_url.' %%%');
	}
	$file['etag'] = md5_file($file['name']);
	if (!empty($_GET['v'])) {
		wrap_cache_header_default('Cache-Control: max-age=31536000'); // 1 year
	}
	return wrap_file_send($file);
}
