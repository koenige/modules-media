<?php 

/**
 * Zugzwang Project
 * Common functions
 * Allgemeine Funktionen
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2020-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * register mf_media_get() as wrap_get_media() if no custom function exists
 */
if (!function_exists('wrap_get_media')) {
	function wrap_get_media($id, $table = 'webpages', $id_field = 'page') {
		return mf_media_get($id, $table, $id_field);
	}
}

/**
 * Read media from database depending on ID
 *
 * @param int $id ID
 * @param string $table name of table, optional
 * @param string $id_field name of id field
 * @return array $media
 *		grouped by images, links
 */
function mf_media_get($id, $table = 'webpages', $id_field = 'page') {
	$multiple_ids = false;
	if (is_array($id)) {
		$id = implode(',', $id);
		$multiple_ids = true;
	}
	$sql = 'SHOW FIELDS FROM media';
	$fields = wrap_db_fetch($sql, '_dummy_', 'numeric');
	
	$sql = 'SELECT %s_id, medium_id, %s_media.sequence
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
			, CASE filetypes.mime_content_type
				WHEN "image" THEN "images"
				WHEN "video" THEN "videos"
				ELSE "links" END
			AS filecategory
		FROM %s_media
		LEFT JOIN media USING (medium_id)
		LEFT JOIN filetypes thumb_filetypes
			ON media.thumb_filetype_id = thumb_filetypes.filetype_id
		LEFT JOIN filetypes
			ON media.filetype_id = filetypes.filetype_id
		WHERE %s_id IN (%s)
		%s
		ORDER BY %s_media.sequence, title, filename
	';
	$where = !empty($_SESSION['logged_in']) ? '' : 'AND published = "yes"';
	if (!in_array('alternative_text', array_column($fields, 'Field'))) {
		$sql = str_replace(', alternative_text', '', $sql);
	}
	$sql = sprintf($sql, $id_field, $table, $table, $id_field, $id, $where, $table);
	if (!$multiple_ids) {
		$media = wrap_db_fetch($sql, ['filecategory', 'medium_id']);
		$media = mf_media_prepare($media);
	} else {
		$media = wrap_db_fetch($sql, [$id_field.'_id', 'filecategory', 'medium_id']);
		foreach ($media as $table_id => $medialist) {
			$media[$table_id] = mf_media_prepare($medialist);
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
	foreach ($media as $filecategory => $files) {
		$media[$filecategory] = wrap_translate($files, 'media');
		foreach ($files as $medium_id => $medium) {
			if ($media[$filecategory][$medium_id]['description'])
				$media[$filecategory][$medium_id]['title'] = $media[$filecategory][$medium_id]['description'];
			$media[$filecategory][$medium_id]['source']
				= trim(markdown($medium['source']));
			if (str_starts_with($media[$filecategory][$medium_id]['source'], '<p>'))
				$media[$filecategory][$medium_id]['source']
					= substr($media[$filecategory][$medium_id]['source'], 3);
			if (str_ends_with($media[$filecategory][$medium_id]['source'], '</p>'))
				$media[$filecategory][$medium_id]['source']
					= substr($media[$filecategory][$medium_id]['source'], 0, -4);
			$media[$filecategory][$medium_id]['filecategory_'.$medium['filecategory']] 
				= $medium['filecategory_'.$medium['filecategory']] = true;
			if ($medium['filetype'] !== 'pdf' AND $filecategory !== 'videos') continue;
			if (!$medium['thumb_extension']) continue;
			$media['images'][$medium_id] = $medium;
		}
	}
	return $media;
}

/**
 * links for media form
 *
 * @param array $variants
 * @return string
 */
function mf_media_switch_links($variants) {
	$text = '';
	foreach ($variants as $variant) {
		$link = $variant['link'] ? '<a href="'.$variant['link'].'">' : '';
		$link_end = $variant['link'] ? '</a>' : '';
		$text .= ' '.sprintf($link.'<img src="%s" alt="%s" title="%s" class="icon">'
			.$link_end, $variant['img'], $variant['alt'], $variant['title']);
	}
	return $text;
}

/**
 * read metadata for youtube movies
 *
 * @param string $video
 * @return array
 */
function mf_media_get_embed_youtube($video) {
	global $zz_setting;
	static $meta;
	if (!empty($meta[$video])) return $meta[$video];
	require_once $zz_setting['core'].'/syndication.inc.php';

	$url = sprintf($zz_setting['youtube_url'], $video);
	$headers[] = sprintf('Cookie: CONSENT=YES+%s', strtoupper($zz_setting['lang']));
	list($status, $headers, $data) = wrap_syndication_retrieve_via_http($url, $headers);

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
