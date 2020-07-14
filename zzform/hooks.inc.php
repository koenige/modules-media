<?php 

/**
 * Zugzwang Project
 * hooks for media module
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_media_hook_embed($ops) {
	$change = [];
	foreach ($ops['planned'] as $index => $table) {
		if ($table['table'] !== 'media') continue;
		if ($table['action'] !== 'insert') continue;
		if ($ops['record_new'][$index]['filetype_id'] === wrap_filetype_id('youtube')) {
			$meta = mod_media_get_embed_youtube($ops['record_new'][$index]['title']);
			if (!$meta) {
				$change['no_validation'] = true;
				$change['validation_fields'][$index]['title']['class'] = 'error';
				$change['validation_fields'][$index]['title']['explanation']
					= wrap_text('There’s no YouTube video with this code');
			} else {
				if (!empty($meta['og:title']) AND !$ops['record_new'][$index]['description'])
					$change['record_replace'][$index]['description'] = $meta['og:title'];
				if (!$ops['record_new'][$index]['source'])
					$change['record_replace'][$index]['source'] = 'YouTube';
				if (!empty($meta['og:image:width']) AND !$ops['record_new'][$index]['width_px'])
					$change['record_replace'][$index]['width_px'] = $meta['og:image:width'];
				if (!empty($meta['og:image:height']) AND !$ops['record_new'][$index]['height_px'])
					$change['record_replace'][$index]['height_px'] = $meta['og:image:height'];
				if (!empty($meta['og:image']) AND !$change['record_replace'][$index]['parameters'])
					$change['record_replace'][$index]['parameters'] = sprintf('og:image=%s&og:video:tag=%s&og:description=%s'
						, $meta['og:image']
						, is_array($meta['og:video:tag']) ? sprintf('[%s]', implode(',', $meta['og:video:tag'])) : $meta['og:video:tag']
						, $meta['og:description']
					);
			}
		}
	}
	return $change;
}
