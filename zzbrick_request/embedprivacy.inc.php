<?php 

/**
 * media module
 * Privacy settings for embeds
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2021, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_media_embedprivacy($params) {
	if (!$embeds = wrap_setting('embed')) {
		$page['text'] = ' ';
		return $page;
	}

	$data = [];
	$selected = !empty($_COOKIE['privacy']) ? explode(',', $_COOKIE['privacy']) : [];
	foreach (array_keys($embeds) as $embed) {
		$data[] = [
			'type' => $embed,
			'identifier' => strtolower(wrap_filename($embed)),
			'selected' => in_array(strtolower(wrap_filename($embed)), $selected) ? true: false,
			$embed => true,
			'privacy' => wrap_setting('embed_privacy['.$embed.']') ? wrap_text(wrap_setting('embed_privacy['.$embed.']')) : ''
		];
	}
	if (!$data)
		$data['no_embeds'] = true;
	else
		$page['head'] = wrap_template('embed-privacy-head');
	$page['text'] = wrap_template('embed-privacy', $data);
	return $page;
}
