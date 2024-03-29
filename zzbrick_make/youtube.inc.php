<?php

/**
 * media module
 * Add YouTube video to database
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2023 Gustaf Mossakowski
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
	$values = [];
	$values['action'] = 'insert';
	$values['GET']['add']['filetype_id'] = wrap_filetype_id('youtube');
	if (empty($settings['main_medium_id'])) {
		$sql = 'SELECT medium_id FROM media
			WHERE filetype_id = %d
			AND filename = "%s"';
		$sql = sprintf($sql
			, wrap_filetype_id('folder')
			, wrap_setting('embed_path_youtube')
		);
		$values['POST']['main_medium_id'] = wrap_db_fetch($sql, '', 'single value');
	} else {
		$values['POST']['main_medium_id'] = $settings['main_medium_id'];
	}
	$values['POST']['title'] = $params[0];
	$values['POST']['source'] = 'YouTube';
	$values['POST']['published'] = 'yes';
	$values['POST']['filename'] = $params[0];
	$ops = zzform_multi('media', $values);
	if (!$ops['id']) {
		wrap_error(sprintf('Could not add YouTube Video %s.', $params[0]));
	}
	$data = $ops['record_new'][0];
	$data['embed_id'] = $params[0];
	$page['text'] = json_encode($data);
	$page['content_type'] = 'json';
	return $page;
}
