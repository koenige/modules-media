<?php

/**
 * media module
 * Add Twitch video to database
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020, 2022-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * add twitch video
 *
 * @param array $params
 */
function mod_media_make_twitch($params, $settings = []) {
	wrap_include('syndication', 'zzwrap');

	$url = sprintf(wrap_setting('twitch_url'), $params[0]);
	list($status, $headers, $data) = wrap_syndication_retrieve_via_http($url);
	if ($status !== 200) {
		wrap_error(sprintf('Twitch Video %s was not found. Status: %d', $params[0], $status));
		return '';
	}
	
	// add medium
	$line = [
		'filetype_id' => wrap_filetype_id('twitch'),
		'main_medium_id' => $settings['main_medium_id'] ?? wrap_id('folders', wrap_setting('media_embed_path_twitch')),
		'title' => $params[0],
		'source' => 'Twitch',
		'published' => 'yes',
		'filename' => $params[0]
	];
	$id = zzform_insert('media', $line);
	if ($id) {
		$sql = 'SELECT * FROM media WHERE medium_id = %d';
		$sql = sprintf($sql, $id);
		$data = wrap_db_fetch($sql);
	}
	$data['embed_id'] = $params[0];
	$page['text'] = json_encode($data);
	$page['content_type'] = 'json';
	return $page;
}
