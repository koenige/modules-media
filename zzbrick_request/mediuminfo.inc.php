<?php 

/**
 * media module
 * show information about a medium
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show information about a medium
 *
 * @param array $params
 * @param array $setting = $zz
 * @return array
 */
function mod_media_mediuminfo($params, $setting) {
	if (!count($params)) return false;

	$filename = implode('/', $params);
	$backlink = '../';
	if (str_ends_with($filename, '/-')) {
		$filename = substr($filename, 0, -2);
		$backlink .= '../';
	}
	$sql = 'SELECT media.*, medium_id, filename, title, description, alternative_text
			, filetypes.filetype, filetypes.mime_content_type
			, filetypes.mime_subtype, filetypes.filetype_description
			, filetypes.extension
			, thumb_filetypes.filetype AS thumb_filetype
			, thumb_filetypes.mime_content_type AS thumb_mime_content_type
			, thumb_filetypes.mime_subtype AS thumb_mime_subtype
			, thumb_filetypes.filetype_description AS thumb_filetype_description
			, thumb_filetypes.extension AS thumb_extension
		FROM media
		LEFT JOIN filetypes USING (filetype_id)
		LEFT JOIN filetypes thumb_filetypes
			ON media.thumb_filetype_id = thumb_filetypes.filetype_id
		WHERE filename = "%s"';
	$sql = sprintf($sql, wrap_db_escape($filename));
	$medium = wrap_db_fetch($sql);
	if (!$medium) return false;
	
	// categories, tags
	$sql = 'SELECT medium_category_id, property
			, categories.category
			, CONCAT("-", IFNULL(SUBSTRING_INDEX(SUBSTRING_INDEX(types.parameters, "&alias=", -1), "&", 1), types.path), "/", SUBSTRING_INDEX(categories.path, "/", -1)) AS path
			, types.category as type
			, IFNULL(SUBSTRING_INDEX(SUBSTRING_INDEX(types.parameters, "&alias=", -1), "&", 1), types.path) AS type_path
		FROM media_categories
		LEFT JOIN categories USING (category_id)
		LEFT JOIN categories types
			ON media_categories.type_category_id = types.category_id
	    WHERE medium_id = %d
	    ORDER BY types.sequence, media_categories.sequence, categories.sequence, categories.path';
	$sql = sprintf($sql, $medium['medium_id']);
	$medium += wrap_db_fetch($sql, ['type_path', 'medium_category_id']);
	$medium['filetype_details'] = wrap_filetypes($medium['filetype']);
	
	// next, prev?
	$page['link'] = mf_media_page_links($medium['medium_id'], $medium['main_medium_id']);
	
	$medium['backlink'] = $backlink;
	$medium['sizes'] = [];
	$original_filename = sprintf('%s%s.%s'
		, $medium['filename']
		, wrap_setting('media_original_filename_extension') ? '.'.wrap_setting('media_original_filename_extension') : ''
		, $medium['extension']
	);
	$medium['sizes'][] = [
		'type' => wrap_text('Original file'),
		'version' => $medium['version'],
		'filename' => $original_filename,
		'file_exists' => file_exists(wrap_setting('media_folder').'/'.$original_filename) ? true : false,
		'width' => $medium['width_px'],
		'height' => $medium['height_px']
	];
	$medium['crop'] = false;
	if ($medium['thumb_filetype']) {
		$media_sizes = wrap_setting('media_sizes');
		$width = array_column($media_sizes, 'width');
		array_multisort($width, SORT_DESC, $media_sizes);
		foreach ($media_sizes as $type => $size) {
			$size['type'] = wrap_text(ucfirst($size['action']).' file').', '.$type;
			$size['version'] = $medium['version'];
			$size['filename'] = sprintf('%s.%s.%s', $medium['filename'], $size['path'], $medium['thumb_extension']);
			$size['file_exists'] = file_exists(wrap_setting('media_folder').'/'.$size['filename']) ? true : false;
			$medium['sizes'][] = $size;
			if (empty($medium['preview_image']) AND $size['action'] === 'thumbnail') {
				$medium['preview_image'] = $size['filename'];
				$medium['preview_title'] = $size['action'].' '.$type;
			}
			if ($size['action'] === 'crop' AND wrap_setting('media_croppr'))
				$medium['crop'] = true;
		}
	} elseif (!empty($medium['filetype_details']['webimage'])) {
		$medium['preview_image'] = sprintf('%s.%s.%s', $medium['filename'], wrap_setting('media_original_filename_extension'), $medium['extension']);
		$medium['preview_title'] = wrap_text('Original file');
	}
	if ($medium['parameters']) {
		parse_str($medium['parameters'], $medium['parameters']);
		$medium += $medium['parameters'];
	}
	$embeds = mf_media_embeds();
	if (in_array($medium['filetype'], $embeds)) {
		$medium['embed_id'] = $medium['title'];
		$medium['embed'] = wrap_template($medium['filetype'], $medium);
	}
	
	$page['h1'] = mf_media_mediapool_title($setting['vars']['title'], $setting['vars']['folder'], $setting['vars']['view']);
	$page['text'] = wrap_template('mediuminfo', $medium);
	if ($medium['crop'])
		$page['head'] = wrap_template('mediuminfo-head');
	return $page;
}
