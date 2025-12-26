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
	function wrap_get_media($id, $table = 'webpages', $id_field = 'page', $where = []) {
		return mf_media_get($id, $table, $id_field, $where);
	}
}

/**
 * Read media from database depending on ID
 *
 * @param int $id ID
 * @param string $table (optional) name of table, optional
 * @param string $id_field (optional) name of id field
 * @param array $where (optional) extra WHERE condition
 * @return array $media
 *		grouped by images, links
 */
function mf_media_get($id, $table = 'webpages', $id_field = 'page', $where = []) {
	if (!wrap_setting('mod_media_install_date')) return [];
	$multiple_ids = false;
	if (is_array($id)) {
		$id = implode(',', $id);
		$multiple_ids = true;
	}

	// fields that do not always exist
	$extra_fields = [];
	$files = wrap_collect_files('configuration/media.sql', 'modules/custom');
	$field_key = sprintf('%s_media__fields', $table);
	foreach ($files as $file) {
		$queries = wrap_sql_file($file);
		foreach ($queries as $key => $line) {
			if ($key !== $field_key) continue;
			$extra_fields = array_merge($extra_fields, $line);
		}
	}
	if (!$extra_fields) {
		// @deprecated, takes extra SQL query
		$sql = 'SHOW FIELDS FROM media';
		$fields = wrap_db_fetch($sql, '_dummy_', 'numeric');
		if (in_array('alternative_text', array_column($fields, 'Field'))) {
			$extra_fields[] = 'alternative_text';
		}
	}
	$extra_fields = $extra_fields ? ','.implode(', ', $extra_fields) : '';
	$table_short = $table;
	if ($pos = strpos($table, ' ')) $table_short = substr($table_short, $pos);
	
	$sql = 'SELECT %s_id, medium_id, %s_media.sequence
			, IF(ISNULL(description), media.title, description) AS title
			, description
			, source, filename, version
			, thumb_filetypes.extension AS thumb_extension
			, media.date, media.time
			, filetypes.extension AS extension
			, filetypes.mime_content_type
			, filetypes.mime_subtype
			, filetypes.filetype
			, filesize
			, filetypes.filetype_description
			, width_px, height_px
			, clipping
			, IF(height_px > width_px, "portrait", "panorama") AS orientation
			, CASE filetypes.mime_content_type
				WHEN "image" THEN "images"
				WHEN "video" THEN "videos"
				ELSE "links" END
			AS filecategory
			%s
		FROM %s_media
		LEFT JOIN media USING (medium_id)
		LEFT JOIN filetypes thumb_filetypes
			ON media.thumb_filetype_id = thumb_filetypes.filetype_id
		LEFT JOIN filetypes
			ON media.filetype_id = filetypes.filetype_id
		WHERE (%s)
		ORDER BY %s_media.sequence, date, time, title, filename
	';
	$where[] = sprintf('%s_id IN (%s)', $id_field, $id);
	// not logged in: show only published media
	if (!wrap_access('media_preview'))
		$where[] = 'published = "yes"';
		
	$sql = sprintf($sql, $id_field, $table_short, $extra_fields, $table, implode(') AND (', $where), $table_short);
	if (!$multiple_ids) {
		$media = wrap_db_fetch($sql, ['filecategory', 'medium_id']);
		$media = mf_media_separate_embeds($media);
		$media = mf_media_prepare($media);
		$media = mf_media_separate_overview($media);
	} else {
		if ($pos = strpos($id_field, '.')) $id_field = substr($id_field, $pos + 1);
		$media = wrap_db_fetch($sql, [$id_field.'_id', 'filecategory', 'medium_id']);
		foreach ($media as $table_id => $medialist) {
			$medialist = mf_media_separate_embeds($medialist);
			$medialist = mf_media_prepare($medialist);
			$media[$table_id] = mf_media_separate_overview($medialist);
		}
	}
	return $media;
}

/**
 * prepare media: translate data, set filecategory for if
 * add pdfs with a thumbnail to 'images'
 *
 * @param array $media
 * @return array
 */
function mf_media_prepare($media) {
	foreach ($media as $filecategory => &$files) {
		$files = wrap_translate($files, 'media');
		foreach ($files as $medium_id => $medium) {
			if ($files[$medium_id]['description'])
				$files[$medium_id]['title'] = $files[$medium_id]['description'];
			$files[$medium_id]['source']
				= markdown_inline($medium['source']);
			$files[$medium_id]['filecategory_'.$medium['filecategory']] 
				= $medium['filecategory_'.$medium['filecategory']] = true;
			if ($medium['filetype'] !== 'pdf' AND $filecategory !== 'videos') continue;
			if (!$medium['thumb_extension']) continue;
			$media['images'][$medium_id] = $medium;
		}
	}
	// put media in images at correct position in sequence
	if (empty($media['images'])) return $media;
	if (count($media['images']) === 1) return $media;
	foreach ($media['images'] as $medium_id => $medium)
		$sequence[$medium_id] = sprintf('%04d-%s-%s-%s-%s', $medium['sequence'], $medium['date'], $medium['time'], $medium['title'], $medium['filename']);
	$keys = array_keys($media['images']);
	array_multisort(
		$sequence, SORT_ASC, SORT_REGULAR,
		$media['images'], $keys
	);
	$media['images'] = array_combine($keys, $media['images']);
	return $media;
}

/**
 * separate embeds from links
 *
 * @param array $media
 * @return array
 */
function mf_media_separate_embeds($media) {
	if (empty($media['links'])) return $media;
	$embeds = mf_media_embeds();
	if (!$embeds) return $media;

	foreach ($media['links'] as $medium_id => $medium) {
		if (!in_array($medium['filetype'], $embeds)) continue;
		$medium['embed_id'] = basename($medium['filename']);
		$media['embeds'][$medium_id] = $medium;
		unset($media['links'][$medium_id]);
	}
	return $media;
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
 * set 'images_detail', 'images_overview'
 * check if an image is marked with `overview_medium`
 * or just use first image
 *
 * @param array $media
 * @return array
 */
function mf_media_separate_overview($media) {
	if (empty($media['images'])) return $media;
	
	$media['images_detail'] = $media['images'];
	// is there an image marked overview?
	foreach ($media['images'] as $medium_id => $medium) {
		if (empty($medium['overview_medium'])) continue;
		$media['images_overview'][$medium_id] = $medium;
		unset($media['images_detail'][$medium_id]);
	}
	// if not, just take first image
	if (empty($media['images_overview'])) {
		foreach ($media['images'] as $medium_id => $medium) {
			$media['images_overview'][$medium_id] = $medium;
			unset($media['images_detail'][$medium_id]);
			break;
		}
	}
	return $media;
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
