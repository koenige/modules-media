<?php

/**
 * media module
 * configuration
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if ($embeds = wrap_setting('embed')) {
	foreach ($embeds as $embed => $url) {
		if ($url === 'true') continue;
		$embed = strtolower($embed);
		wrap_setting_add('brick_shortcuts', sprintf('page %s', $embed));

		if (!wrap_setting('media_embed_path_'.$embed))
			wrap_setting('media_embed_path_'.$embed, $embed);
		wrap_setting($embed.'_embed_url', $url);
		if (!wrap_setting($embed.'_url'))
			wrap_setting($embed.'_url', $url);
	}
}
