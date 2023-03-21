<?php

/**
 * media module
 * configuration
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


wrap_setting_add('brick_types_translated', ['image' => 'page']);
wrap_setting_add('brick_page_shortcuts', 'image');
wrap_setting_add('brick_types_translated', ['doc' => 'page']);
wrap_setting_add('brick_page_shortcuts', 'doc');
wrap_setting_add('brick_types_translated', ['video' => 'page']);
wrap_setting_add('brick_page_shortcuts', 'video');

if ($embeds = wrap_setting('embed')) {
	foreach ($embeds as $embed => $url) {
		if ($url === 'true') continue;
		$embed = strtolower($embed);
		wrap_setting_add('brick_types_translated', [$embed => 'page']);
		wrap_setting_add('brick_page_shortcuts', $embed);

		if (!wrap_setting('embed_path_'.$embed))
			wrap_setting('embed_path_'.$embed, $embed);
		wrap_setting($embed.'_embed_url', $url);
		if (!wrap_setting($embed.'_url'))
			wrap_setting($embed.'_url', $url);
	}
}
