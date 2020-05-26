<?php 

/**
 * Zugzwang Project
 * Privacy settings for embeds
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_media_embedprivacy($params) {
	global $zz_setting;
	
	if (empty($zz_setting['embed'])) {
		$page['text'] = ' ';
		return $page;
	}

	$data = [];
	$selected = !empty($_COOKIE['privacy']) ? explode(',', $_COOKIE['privacy']) : [];
	foreach (array_keys($zz_setting['embed']) as $embed) {
		$data[] = [
			'type' => $embed,
			'selected' => in_array(strtolower($embed), $selected) ? true: false,
			$embed => true
		];
	}
	if (!$data)
		$data['no_embeds'] = true;
	else
		$page['head'] = wrap_template('embed-privacy-head');
	$page['text'] = wrap_template('embed-privacy', $data);
	return $page;
}
