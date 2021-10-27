<?php 

/**
 * media module
 * Output of files from protected area
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show information on a medium
 *
 * @param array $params
 * @return array
 */
function mod_media_mediuminfo($params) {
	global $zz_setting;
	if (count($params) !== 1) return false;

	$filename = $params[0];
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
	
	$medium['backlink'] = $backlink;
	$medium['sizes'] = [];
	$master_filename = sprintf('%s.master.%s', $medium['filename'], $medium['extension']);
	$medium['sizes'][] = [
		'type' => 'master',
		'version' => $medium['version'],
		'filename' => $master_filename,
		'file_exists' => file_exists($zz_setting['media_folder'].'/'.$master_filename) ? true : false,
		'width' => $medium['width_px'],
		'height' => $medium['height_px']
	];
	$medium['crop'] = false;
	foreach ($zz_setting['media_sizes'] as $type => $size) {
		$size['type'] = $type;
		$size['version'] = $medium['version'];
		$size['filename'] = sprintf('%s.%s.%s', $medium['filename'], $size['path'], $medium['thumb_extension']);
		$size['file_exists'] = file_exists($zz_setting['media_folder'].'/'.$size['filename']) ? true : false;
		$medium['sizes'][] = $size;
		if (empty($medium['preview_image']) AND $size['action'] === 'thumbnail') {
			$medium['preview_image'] = $size['filename'];
			$medium['preview_title'] = $size['action'].' '.$type;
		}
		if ($size['action'] === 'crop' AND !empty($zz_setting['media_croppr']))
			$medium['crop'] = true;
	}
	$page['text'] = wrap_template('mediuminfo', $medium);
	if ($medium['crop'])
		$page['head'] = wrap_template('mediuminfo-head');
	return $page;
}
