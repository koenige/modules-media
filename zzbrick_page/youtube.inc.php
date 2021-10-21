<?php

/**
 * media module
 * Output YouTube video
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * YouTube video
 *
 * @param array $params
 */
function page_youtube(&$params, $page) {
	global $zz_setting;
	if (count($params) !== 1) {
		return '';
	}
	$video = array_shift($params);

	$sql = 'SELECT medium_id
			, title, description, source, width_px, height_px, parameters
			, filename, SUBSTRING_INDEX(filename, "/", -1) AS embed_id
		FROM media
		WHERE filetype_id = %d
		AND filename = "%s/%s"';
	$sql = sprintf($sql
		, wrap_filetype_id('youtube')
		, $zz_setting['embed_path_youtube']
		, wrap_db_escape($video)
	);
	$medium = wrap_db_fetch($sql);

	if (!$medium) {
		$medium = brick_format('%%% make youtube '.$video.' %%%');
		if (!$medium) return '';
		$medium = json_decode($medium['text'], true);
	}
	$text = wrap_template('youtube', $medium);
	return $text;
}
