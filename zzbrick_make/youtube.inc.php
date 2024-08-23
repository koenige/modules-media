<?php

/**
 * media module
 * Add YouTube video to database
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * add YouTube video
 *
 * @param array $params
 */
function mod_media_make_youtube($params, $settings = []) {
	$meta = mf_media_get_embed_youtube($params[0]);
	if (!$meta) return false;

	// add medium
	$line = [
		'filetype_id' => wrap_filetype_id('youtube'),
		'main_medium_id' => $settings['main_medium_id'] ?? wrap_id('folders', wrap_setting('media_embed_path_youtube')),
		'title' => $params[0],
		'source' => 'YouTube',
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
