<?php 

/**
 * media module
 * Output of files from protected area
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2014-2015, 2017-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Output of a file below DOCUMENT ROOT
 *
 * @param array $params
 *		[0]: Type
 *		[1]...: folder
 *		[n]: filename .tn.typ
 * @return mixed (standard: file will be sent, or array $page, or redirect)
 */
function mod_media_medium($params) {
	if (!$params) return false;
	$filename = implode('/', $params);
	$parsed = pathinfo($filename);
	$extension = $parsed['extension'] ?? '';
	if (!empty($parsed['dirname']) && $parsed['dirname'] !== '.') {
		$identifier = $parsed['dirname'].'/'.$parsed['filename'];
	} else {
		$identifier = $parsed['filename'];
	}
	// sometimes, browsers coming from search engines interpret ? wrong as %3F
	$new_url = '';
	if ($redirect = strpos($extension, '%3Fv=')) {
		$extension = substr($extension, 0, $redirect);
		$filename = substr($filename, 0, strpos($filename, '%3Fv='));
		$new_url = explode('%3Fv=', wrap_url('path'));
		$new_url = implode('?v=', $new_url);
	}

	$media_sizes = wrap_setting('media_sizes');
	$media_sizes['original'] = [
		'path' => wrap_setting('media_original_filename_extension')
	];
	$media_size = '';
	if (($pos = strrpos($identifier, '.')) === false) {
		$media_size = 'original';
	} else {
		foreach ($media_sizes as $size) {
			if (!str_ends_with($identifier, '.'.$size['path'])) continue;
			$identifier = substr($identifier, 0, - strlen($size['path']) - 1);
			$media_size = $size['path'];
			break;
		}
		if (!$media_size) {
			// get a possible old media size
			$old_media_size = substr($identifier, $pos + 1);
			if (array_key_exists($old_media_size, wrap_setting('media_sizes_redirect'))) {
				$identifier = substr($identifier, 0, $pos);
				$old_filename = $filename;
				$parts = explode('.', $filename);
				$key = $extension ? count($parts) - 2 : count($parts) - 1;
				$media_size = wrap_setting('media_sizes_redirect')[$old_media_size];
				if (!in_array($media_size, array_column($media_sizes, 'path')))
					wrap_error(wrap_text(
						'Configuration of `media_sizes_redirect` is wrong, pointing to a non-existent destination: %s',
						['values' => [$media_size]]
					), E_USER_NOTICE);
				$parts[$key] = str_replace($old_media_size, $media_size, $parts[$key]);
				$filename = implode('.', $parts);
				// overwrite $new_url if set before, but this does not matter
				// since we ignore the ?v= path completely here
				$new_url = str_replace($old_filename, $filename, wrap_url('path'));
			} else {
				return false;
			}
		}
	}

	foreach (wrap_setting('media_file_variants') as $variant) {
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
			, filename
			, filetypes.extension AS extension
			, thumb_filetypes.extension AS thumb_extension
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

	$embed_page = mod_media_medium_embed($file, $identifier);
	if ($embed_page !== null) return $embed_page;

	$file['name'] = mf_media_filename($file, $media_size, true);
	// Check if file exists
	if (!file_exists($file['name'])) return false;
	if (is_dir($file['name'])) return false;
	if ($new_url) {
		return wrap_redirect($new_url, 303);
	}
	$file['etag'] = md5_file($file['name']);
	if ($media_size !== 'original')
		$file['send_as'] .= ' '.$media_size;
	if (!empty($_GET['v'])) {
		if (!is_numeric($_GET['v'])) wrap_setting('cache', false);
		wrap_cache_header_default('Cache-Control: max-age=31536000'); // 1 year
	}
	wrap_send_file($file);
}

/**
 * Embedded medium (iframe, etc.): inactive preview page or redirect to provider URL.
 *
 * @param array $file Medium row including filetype, send_as, …
 * @param string $identifier Canonical filename without thumbnail suffix variant
 * @return array|bool|null Embed page (`array`), blocked request (`false`),
 * 		redirect, or `null` when this request is ordinary file delivery
 */
function mod_media_medium_embed($file, $identifier) {
	if (!wrap_setting('embed')) return null;

	$embeds = [];
	foreach (wrap_setting('embed') as $key => $value)
		$embeds[strtolower($key)] = $value;

	if (!array_key_exists($file['filetype'], $embeds)) return null;

	$code = explode('/', $identifier);
	$code = array_pop($code);
	$url = sprintf($embeds[$file['filetype']], $code);
	if (!empty($_GET['inactive'])) {
		if (is_array($_GET['inactive'])) return false;
		if ($_GET['inactive'].'' !== '1') return false;
		$file['url'] = $url;
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
	}
	return wrap_redirect($url);
}
