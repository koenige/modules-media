<?php

/**
 * Zugzwang Project
 * Output YouTube video
 *
 * http://www.zugzwang.org/modules/news
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
		$medium = page_youtube_add_video($video);
		if (!$medium) return '';
	}
	$text = wrap_template('youtube', $medium);
	return $text;
}

function page_youtube_add_video($video) {
	global $zz_conf;
	global $zz_setting;
	require_once $zz_conf['dir'].'/zzform.php';

	$meta = mod_media_get_embed_youtube($video);
	if (!$meta) return false;

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
		, $zz_setting['embed_path_youtube']
	);
	$values['GET']['add']['filetype_id'] = wrap_filetype_id('youtube');
	$values['POST']['main_medium_id'] = wrap_db_fetch($sql, '', 'single value');
	$values['POST']['title'] = $video;
	if (!empty($meta['og:title']))
		$values['POST']['description'] = $meta['og:title'];
	$values['POST']['source'] = 'YouTube';
	$values['POST']['published'] = 'yes';
	$values['POST']['filename'] = sprintf('%s/%s', $zz_setting['embed_path_youtube'], $video);
	if (!empty($meta['og:image:width']))
		$values['POST']['width_px'] = $meta['og:image:width'];
	if (!empty($meta['og:image:height']))
		$values['POST']['height_px'] = $meta['og:image:height'];
	if (!empty($meta['og:image']))
		$values['POST']['parameters'] = sprintf('og:image=%s&og:video:tag=%s&og:description=%s'
			, $meta['og:image']
			, is_array($meta['og:video:tag']) ? sprintf('[%s]', implode(',', $meta['og:video:tag'])) : $meta['og:video:tag']
			, $meta['og:description']
		);
	$ops = zzform_multi('media', $values);
	if (!$ops['id']) {
		wrap_error(sprintf('Could not add YouTube Video %s.', $video));
	}
	$values['POST']['medium_id'] = $ops['id'];
	$values['POST']['embed_id'] = $video;
	return $values['POST'];
}
