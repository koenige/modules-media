<?php

/**
 * media module
 * get media data
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2020-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Read media from database depending on ID
 *
 * @param int $id ID
 * @param string $table name of table, optional
 * @param string $id_field name of id field
 * @param array $settings
 *		array where (optional) extra WHERE condition
 *		bool include_unpublished if true, does not check user’s rights for unpublished media
 * @return array $media
 *		grouped by images, links
 */
function mf_media_get($id, $table, $id_field, $settings = []) {
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
	if (in_array($table, ['categories']))
		$detail_media_table = sprintf('media_%s', $table);
	else
		$detail_media_table = sprintf('%s_media', $table);

	$sql = 'SELECT %s_id, medium_id, detail_media.sequence
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
		FROM %s detail_media
		LEFT JOIN media USING (medium_id)
		LEFT JOIN filetypes thumb_filetypes
			ON media.thumb_filetype_id = thumb_filetypes.filetype_id
		LEFT JOIN filetypes
			ON media.filetype_id = filetypes.filetype_id
		WHERE (%s)
		ORDER BY detail_media.sequence, date, time, title, filename
	';
	$settings['where'][] = sprintf('%s_id IN (%s)', $id_field, $id);
	// not logged in: show only published media
	if (empty($settings['include_unpublished']) AND !wrap_access('media_preview'))
		$settings['where'][] = 'published = "yes"';
		
	$sql = sprintf($sql, $id_field, $extra_fields, $detail_media_table, implode(') AND (', $settings['where']));
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
