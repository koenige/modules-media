<?php

/**
 * Zugzwang Project
 * Output Twitch video
 *
 * http://www.zugzwang.org/modules/news
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Twitch video
 *
 * @param array $params
 */
function page_twitch(&$params, $page) {
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
		, wrap_filetype_id('twitch')
		, $zz_setting['embed_path_twitch']
		, wrap_db_escape($video)
	);
	$medium = wrap_db_fetch($sql);

	if (!$medium) {
		$medium = page_twitch_add_video($video);
		if (!$medium) return '';
	}
	$text = wrap_template('twitch', $medium);
	return $text;
}

function page_twitch_add_video($video) {
	global $zz_setting;
	global $zz_conf;
	require_once $zz_setting['core'].'/syndication.inc.php';
	require_once $zz_conf['dir'].'/zzform.php';

	$url = sprintf($zz_setting['twitch_url'], $video);
	list($status, $headers, $data) = wrap_syndication_retrieve_via_http($url);
	if ($status !== 200) {
		wrap_error(sprintf('Twitch Video %s was not found. Status: %d', $video, $status));
		return '';
	}
	
	// add medium
	if (!empty($_SESSION['user']))
		$zz_conf['user'] = $_SESSION['user'];
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
	$values['POST']['title'] = $video;
	$values['POST']['source'] = 'Twitch';
	$values['POST']['published'] = 'yes';
	$values['POST']['filename'] = sprintf('%s/%s', $zz_setting['embed_path_twitch'], $video);
	$ops = zzform_multi('media', $values);
	if (!$ops['id']) {
		wrap_error(sprintf('Could not add Twitch Video %s.', $video));
	}
	$values['POST']['medium_id'] = $ops['id'];
	$values['POST']['embed_id'] = $video;
	return $values['POST'];
}
