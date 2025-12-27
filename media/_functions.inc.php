<?php 

/**
 * media module
 * Common functions
 * Allgemeine Funktionen
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2020-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * register mf_media_get() as wrap_get_media() if no custom function exists
 */
if (!function_exists('wrap_get_media')) {
	wrap_include('media', 'media');
	function wrap_get_media($id, $table = 'webpages', $id_field = 'page', $where = []) {
		return mf_media_get($id, $table, $id_field, $where);
	}
}

/**
 * get a list of possible embeds
 *
 * @return array
 */
function mf_media_embeds() {
	if (!$embeds = wrap_setting('embed')) return [];

	$embeds = array_keys($embeds);
	foreach ($embeds as $index => $embed) {
		$embeds[$index] = strtolower($embed);
	}
	return $embeds;
}

/**
 * read metadata for youtube movies
 *
 * @param string $video
 * @return array
 */
function mf_media_get_embed_youtube($video) {
	static $meta = [];
	if (!empty($meta[$video])) return $meta[$video];
	wrap_include('syndication', 'zzwrap');

	$url = sprintf(wrap_setting('youtube_url'), $video);
	$headers[] = sprintf('Cookie: CONSENT=NO+%s', strtoupper(wrap_setting('lang')));
	list($status, $headers, $data) = wrap_syndication_http_request($url, $headers);

	// get opengraph metadata
	if ($status === 200) {
		preg_match_all('/<meta property=["\'](.+?)["\'] content=["\'](.+?)["\']>/', $data, $matches);
		foreach ($matches[1] as $index => $key) {
			if (!empty($meta[$video][$key])) {
				if (!is_array($meta[$video][$key])) $meta[$video][$key] = [$meta[$video][$key]];
				$meta[$video][$key][] = $matches[2][$index];
			} else {
				$meta[$video][$key] = $matches[2][$index];
			}
		}
		if (empty($meta[$video])) $status = 404;
	}
	if (!in_array($status, [200, 429])) {
		wrap_error(sprintf('YouTube Video %s was not found. Status: %d. Headers: %s. Response: %s', $video, $status, json_encode($headers), json_encode($data)));
		return [];
	}

	$meta[$video]['video'] = $video;
	return $meta[$video];
}

/**
 * get filetypes data from cfg when adding new filetype in table
 *
 * @param array $cfg
 * @return array
 */
function mf_media_filetypes_cfg($cfg) {
	if (empty($cfg)) return [];
	$mime_type = explode('/', $cfg['mime'][0]);
	$data = [
		!empty($cfg['description']) ? wrap_text($cfg['description']) : '',
		$mime_type[0],
		$mime_type[1],
		$cfg['extension'][0]
	];
	return $data;
}

/**
 * get sizes for thumbnail images depending on image, dimension and size
 *
 * @param array $image
 * @param string $dimension (width, height)
 * @param string $size (wanted key of setting media_sizes)
 * @return string
 */
function mf_media_image_size($image, $dimension, $size) {
	if (!$image['height_px']) return '';
	if (!$image['width_px']) return '';
	if (!$size = wrap_setting('media_sizes['.$size.']')) return false;
	switch ($dimension) {
	case 'width':
		if ($image['orientation'] === 'panorama') return $size['width'];
		return round($size['height'] / $image['height_px'] * $image['width_px']);
	case 'height':
		if ($image['orientation'] === 'portrait') return $size['height'];
		return round($size['width'] / $image['width_px'] * $image['height_px']);
	}
	return '';
}

/**
 * get opengraph image tags from given image
 *
 * @param array $image
 * @param string $size (optional; if not found as key, check against path)
 * @return array
 */
function mf_media_opengraph_image($image, $size = '') {
	if (!$media_sizes = wrap_setting('media_sizes')) return [];
	if (!$size) {
		$size = wrap_setting('opengraph_image_size') ?? wrap_setting('media_standard_image_size');
		if (!$size) return [];
	}
	$msize = [];
	if (array_key_exists($size, $media_sizes))
		$msize = $media_sizes[$size];
	else
		foreach ($media_sizes as $key => $media_size) {
			if ($media_size['path'].'' !== $size.'') continue;
			$msize = $media_size;
			$size = $key;
		}
	if (!$msize) return [];

	// we need a binary image, JPEG, PNG, GIF or WebP
	if (!$image['thumb_extension']) return [];

	$og = [];
	$og['og:image'] = sprintf(
		'%s%s/%s.%s.%s?v=%d', wrap_setting('host_base'), wrap_setting('files_path')
		, $image['filename'], $msize['path'], $image['thumb_extension']
		, $image['version']
	);
	$og['og:image:width'] = mf_media_image_size($image, 'width', $size);
	$og['og:image:height'] = mf_media_image_size($image, 'height', $size);
	$og['og:image:alt'] = $image['alternative_text']
		? $image['alternative_text'] : $image['title'];
	return $og;
}

/**
 * get single medium from medium_id that’s in the settings
 * e. g. for default images
 *
 * @param string $setting
 * @return array
 */
function mf_media_medium_from_setting($setting) {
	$medium_id = wrap_setting(sprintf('%s_medium_id', $setting));
	if (!$medium_id) return [];

	$sql = 'SELECT medium_id
			, IF(ISNULL(description), media.title, description) AS title
			, description, alternative_text
			, source, filename, version
			, thumb_filetypes.extension AS thumb_extension
			, media.date
			, filetypes.extension AS extension
			, filetypes.mime_content_type
			, filetypes.mime_subtype
			, filetypes.filetype
			, filesize
			, filetypes.filetype_description
			, width_px, height_px
			, IF(height_px > width_px, "portrait", "panorama") AS orientation
		FROM media
		LEFT JOIN filetypes thumb_filetypes
			ON media.thumb_filetype_id = thumb_filetypes.filetype_id
		LEFT JOIN filetypes
			ON media.filetype_id = filetypes.filetype_id
		WHERE medium_id = %d
		%s
	';
	$where = wrap_access('media_preview') ? '' : 'AND published = "yes"';
	$sql = sprintf($sql, $medium_id, $where);
	$images = wrap_db_fetch($sql, 'medium_id');
	return $images;
}

/**
 * get all media from a folder
 *
 * @param int $folder
 * @return array
 */
function mf_media_media_from_folder($folder) {
	$folder_medium_id = !is_numeric($folder) ? wrap_id('folders', $folder) : $folder;
	if (!$folder_medium_id) return [];

	$sql = 'SELECT medium_id
			, IF(ISNULL(description), media.title, description) AS title
			, description, alternative_text
			, source, filename, version
			, thumb_filetypes.extension AS thumb_extension
			, media.date
			, filetypes.extension AS extension
			, filetypes.mime_content_type
			, filetypes.mime_subtype
			, filetypes.filetype
			, filesize
			, filetypes.filetype_description
			, width_px, height_px
			, IF(height_px > width_px, "portrait", "panorama") AS orientation
		FROM media
		LEFT JOIN filetypes thumb_filetypes
			ON media.thumb_filetype_id = thumb_filetypes.filetype_id
		LEFT JOIN filetypes
			ON media.filetype_id = filetypes.filetype_id
		WHERE main_medium_id = %d
		%s
		ORDER BY sequence, title, filename
	';
	$where = wrap_access('media_preview') ? '' : 'AND published = "yes"';
	$sql = sprintf($sql, $folder_medium_id, $where);
	$images = wrap_db_fetch($sql, 'medium_id');
	return $images;
}

/**
 * prepare all given media files as mail attachments
 *
 * @param array $data
 * @param string $image_size (optional)
 * @return array
 */
function mf_media_mail_attachments(&$data, $image_size = 'media_standard_image_size') {
	$files = [];
	$keys = ['images', 'links'];
	$of = wrap_setting('media_original_filename_extension') ? sprintf('.%s', wrap_setting('media_original_filename_extension')) : '';
	foreach ($keys as $key) {
		if (!array_key_exists($key, $data)) continue;
		foreach ($data[$key] as $medium_id => &$file) {
			if (!$file) continue; // dummy entry for zzbrick @todo remove this
			switch ($key) {
			case 'images':
				if (!$file['thumb_extension']) {
					if (!wrap_webimage($file['filetype'])) continue 2; // no image possible
					$file['path'] = sprintf('%s%s.%s', $file['filename'], $of, $file['extension']);
				} else {
					$file['path'] = sprintf('%s.%s.%s', $file['filename'], wrap_setting($image_size), $file['thumb_extension']);
				}
				$file['disposition'] = 'inline';
				break;
			case 'links':
				$file['path'] = sprintf('%s%s.%s', $file['filename'], $of, $file['extension']);
				$file['disposition'] = 'attachment';
				break;
			}
			$file['path_local'] = wrap_setting('media_folder').'/'.$file['path'];
			$file['cid'] = sprintf('%s@%s', $file['path'], wrap_setting('hostname'));
			$file['medium_description'] = $file['description']; // to avoid descriptions in loops to be taken from articles
			$files[] = $file;
		}
	}
	return $files;
}
