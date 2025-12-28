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
 * @param array $settings {
 *     Optional. Array of settings.
 *
 *     @type int  $main_medium_id      ID of parent medium to filter by folder. Default empty.
 *     @type bool $include_unpublished Whether to include unpublished media without 
 *                                     checking user rights. Default false.
 * }
 * @return array {
 *     Media files grouped by category
 *
 *     @type array $images          Media files with mime type image/*
 *     @type array $videos          Media files with mime type video/*
 *     @type array $links           Other media files
 *     @type array $embeds          Embeddable content (subset of links)
 *     @type array $images_overview Image(s) marked as overview or first image
 *     @type array $images_detail   Remaining images after overview extraction
 * }
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

	// WHERE condition
	$where[] = sprintf('%s_id IN (%s)', $id_field, $id);
	if (empty($settings['include_unpublished']) AND !wrap_access('media_preview'))
		// not logged in: show only published media
		$where[] = 'published = "yes"';
	if (!empty($settings['main_medium_id']))
		$where[] = sprintf('media.main_medium_id = %d', $settings['main_medium_id']);
	$where = implode(') AND (', $where);

	$sql = sprintf($sql, $id_field, $extra_fields, $detail_media_table, $where);
	if (!$multiple_ids) {
		$media = wrap_db_fetch($sql, ['filecategory', 'medium_id']);
		$media = mf_media_separate_embeds($media);
		$media = mf_media_prepare($media);
		$media = mf_media_separate_overview($media);
	} else {
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
 * Separate embeds from links
 *
 * Moves media items with embeddable filetypes (e.g. YouTube, Vimeo) from the 
 * 'links' category to a separate 'embeds' category and adds an embed_id field.
 *
 * @param array $media {
 *     Media array grouped by category
 *
 *     @type array $links Optional. Media items that may contain embeddable content
 * }
 * @return array Modified media array with embeds separated from links
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
 * Prepare media data for output
 *
 * Translates media data, processes markdown in source field, sets filecategory 
 * flags, and adds PDF files and videos with thumbnails to the 'images' category.
 * Sorts images by sequence, date, time, title, and filename.
 *
 * @param array $media {
 *     Media array grouped by category
 *
 *     @type array $images Optional. Image media items
 *     @type array $videos Optional. Video media items
 *     @type array $links  Optional. Other media items
 *     @type array $embeds Optional. Embeddable media items
 * }
 * @return array Prepared media array with translated data and proper categorization
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
 * Separate images into overview and detail categories
 *
 * Creates 'images_overview' containing images marked with 'overview_medium' flag
 * (or the first image if none marked), and 'images_detail' with remaining images.
 *
 * @param array $media {
 *     Media array grouped by category
 *
 *     @type array $images Optional. Image media items. Each item may contain:
 *         - 'overview_medium' (bool) Flag to mark image as overview image
 * }
 * @return array {
 *     Media array with separated image categories
 *
 *     @type array $images_overview First image or image(s) marked as overview
 *     @type array $images_detail   Remaining images not used as overview
 * }
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
