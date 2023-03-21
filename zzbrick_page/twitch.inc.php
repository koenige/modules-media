<?php

/**
 * media module
 * Output Twitch video
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Twitch video
 *
 * @param array $params
 */
function page_twitch(&$params, $page) {
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
		, wrap_filetype_id('twitch')
		, wrap_setting('embed_path_twitch')
		, wrap_db_escape($video)
	);
	$medium = wrap_db_fetch($sql);

	if (!$medium) {
		$medium = brick_format('%%% make twitch '.$video.' %%%');
		if (!$medium) return '';
		$medium = json_decode($medium['text'], true);
	}
	$text = wrap_template('twitch', $medium);
	return $text;
}
