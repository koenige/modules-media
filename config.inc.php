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
	$zz_setting['youtube_url'] = 'https://www.youtube.com/watch?v=%s';

	foreach ($zz_setting['embed'] as $embed => $url) {
		$embed = strtolower($embed);
		$zz_setting['brick_types_translated'][$embed] = 'page';
		$zz_setting['brick_page_shortcuts'][] = $embed;

		if (empty($zz_setting['embed_path_'.$embed]))
			$zz_setting['embed_path_'.$embed] = $embed;
		$zz_setting[$embed.'_embed_url'] = $url;
		if (empty($zz_setting[$embed.'_url']))
			$zz_setting[$embed.'_url'] = $url;
	}

}
