<?php 

/**
 * media module
 * Output of files from protected area
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2014-2015, 2017-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Output of a file below DOCUMENT ROOT
 *
 * @param array $params
 *		[0]: Type
 *		[1]...: folder
 *		[n]: filename .tn.typ
 * @return void (file will be sent)
 */
function mod_media_medium($params) {
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

	$media_sizes = wrap_setting('media_sizes');
	$media_sizes['original'] = ['path' => wrap_setting('media_original_filename_extension')];
	$media_size = '';
	foreach ($media_sizes as $size) {
		if (!$size['path']) {
			if (!strstr($identifier, '.')) {
				$media_size = $size['path'];
				break;
			}
		} elseif (substr($identifier, - (strlen($size['path']) + 1)) === '.'.$size['path']) {
			$identifier = substr($identifier, 0, - strlen($size['path']) - 1);
			$media_size = $size['path'];
			break;
		}
	}
	$variants = wrap_setting('media_file_variants');
	foreach ($variants as $variant) {
		if (substr($identifier, - (strlen($variant) + 1)) === '-'.$variant) {
			$identifier = substr($identifier, 0, - strlen($variant) - 1);
			break;
		}
	}
	$sql = 'SELECT medium_id, IF(published = "yes", 1, NULL) AS published
			, title AS send_as
			, description
			, filetypes.filetype_id
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
		wrap_include('auth', 'zzwrap');
		wrap_auth(1);
	}

	// is it an embedded medium?
	if (wrap_setting('embed')) {
		$embeds = [];
		foreach (wrap_setting('embed') as $key => $value)
			$embeds[strtolower($key)] = $value;
		if (array_key_exists($file['filetype'], $embeds)) {
			$code = explode('/', $identifier);
			$code = array_pop($code);
			$url = sprintf($embeds[$file['filetype']], $code);
			if (!empty($_GET['inactive'])) {
				if (is_array($_GET['inactive'])) return false;
				if ($_GET['inactive'].'' !== '1') return false;
				$file['url'] = $url;
				$file['privacy_policy_url'] = wrap_setting('privacy_policy_url');
				$page['query_strings'] = ['inactive', 'lang'];
				if (!empty($_GET['lang']) AND wrap_setting('languages_allowed')
					AND in_array($_GET['lang'], wrap_setting('languages_allowed'))) {
					wrap_setting('lang', $_GET['lang']);
				}
				// different language? translate filetypes if set for translation
				$file = wrap_translate($file, 'filetypes', 'filetype_id');
				$page['title'] = $file['filetype_description'].': '.$file['send_as'];
				$page['template'] = 'embed';
				$page['url_ending'] = 'none';
				$page['text'] = wrap_template('embed', $file);
				return $page;
			} else {
				return wrap_redirect($url);
			}
		}
	}
	$file['name'] = sprintf('%s/%s', wrap_setting('media_folder'), $filename);
	// Check if file exists
	if (!file_exists($file['name'])) {
		if ($media_size) return false;
		$pos = strrpos($file['name'], '.');
		$file['name'] = substr($file['name'], 0, $pos).'.'.wrap_setting('media_original_filename_extension').substr($file['name'], $pos);
		if (!file_exists($file['name'])) return false;
	}
	if (is_dir($file['name'])) return false;
	if ($redirect) {
		return wrap_redirect($new_url, 303);
	}
	$file['etag'] = md5_file($file['name']);
	if ($media_size)
		$file['send_as'] .= ' '.$media_size;
	if (!empty($_GET['v'])) {
		if (!is_numeric($_GET['v'])) wrap_setting('cache', false);
		wrap_cache_header_default('Cache-Control: max-age=31536000'); // 1 year
	}
	wrap_send_file($file);
}
