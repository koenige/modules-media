<?php

/**
 * media module
 * Add Twitch video to database
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020, 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * add twitch video
 *
 * @param array $params
 */
function mod_media_make_twitch($params) {
	global $zz_setting;
	require_once $zz_setting['core'].'/syndication.inc.php';

	$url = sprintf($zz_setting['twitch_url'], $params[0]);
	list($status, $headers, $data) = wrap_syndication_retrieve_via_http($url);
	if ($status !== 200) {
		wrap_error(sprintf('Twitch Video %s was not found. Status: %d', $params[0], $status));
		return '';
	}
	
	// add medium
	$values = [];
	$values['action'] = 'insert';
	$sql = 'SELECT medium_id FROM media
		WHERE filetype_id = %d
		AND filename = "%s"';
	$sql = sprintf($sql
		, wrap_filetype_id('folder')
		, $zz_setting['embed_path_twitch']
	);
	$values['GET']['add']['filetype_id'] = wrap_filetype_id('twitch');
	$values['POST']['main_medium_id'] = wrap_db_fetch($sql, '', 'single value');
	$values['POST']['title'] = $params[0];
	$values['POST']['source'] = 'Twitch';
	$values['POST']['published'] = 'yes';
	$values['POST']['filename'] = sprintf('%s/%s', $zz_setting['embed_path_twitch'], $params[0]);
	$ops = zzform_multi('media', $values);
	if (!$ops['id']) {
		wrap_error(sprintf('Could not add Twitch Video %s.', $params[0]));
	}
	$data = $ops['record_new'][0];
	$data['embed_id'] = $params[0];
	$page['text'] = json_encode($data);
	$page['content_type'] = 'json';
	return $page;
}
