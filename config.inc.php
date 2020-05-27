<?php

/**
 * Zugzwang Project
 * configuration for media module
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!empty($zz_setting['embed'])) {
	foreach (array_keys($zz_setting['embed']) as $embed) {
		$embed = strtolower($embed);
		$zz_setting['brick_types_translated'][$embed] = 'page';
		$zz_setting['brick_page_shortcuts'][] = $embed;

		$zz_setting['embed_path'][$embed] = '/'.$embed;
	}

	$zz_setting['youtube_url'] = 'https://www.youtube.com/watch?v=%s';
	$zz_setting['youtube_embed_url'] = 'https://www.youtube-nocookie.com/embed/%s';
}

