<?php 

/**
 * media module
 * hooks
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2021, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mf_media_hook_embed($ops) {
	$change = [];
	foreach ($ops['planned'] as $index => $table) {
		if ($table['table'] !== 'media') continue;
		if ($table['action'] !== 'insert') continue;
		if ($ops['record_new'][$index]['filetype_id'] === wrap_filetype_id('youtube')) {
			$meta = mf_media_get_embed_youtube($ops['record_new'][$index]['title']);
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
				if (!empty($meta['og:image']) AND empty($change['record_replace'][$index]['parameters'])) {
					if (empty($meta['og:video:tag'])) $meta['og:video:tag'] = '';
					$change['record_replace'][$index]['parameters'] = sprintf('og:image=%s&og:video:tag=%s&og:description=%s'
						, $meta['og:image']
						, is_array($meta['og:video:tag']) ? sprintf('[%s]', implode(',', $meta['og:video:tag'])) : $meta['og:video:tag']
						, $meta['og:description']
					);
				}
			}
		}
	}
	return $change;
}

/**
 * check if filetype allows thumbnails
 *
 * @param array $ops
 * @return array
 */
function mf_media_hook_thumb($ops) {
	if (empty($ops['record_new'][0]['thumb_filetype_id'])) return [];
	
	$filetypes = wrap_filetype_id(false, 'list');
	$filetypes = array_flip($filetypes);
	$filetype = $filetypes[$ops['record_new'][0]['filetype_id']];
	$filetype_def = wrap_filetypes($filetype);
	// does filetype allow thumbnail?
	if (!empty($filetype_def['thumbnail'])) return [];

	$change['record_replace'][0]['thumb_filetype_id'] = NULL;
	return $change;
}

/**
 * rename all files and folders inside a top folder if it is renamed
 *
 * @param array $ops
 * @return array
 */
function mf_media_hook_rename_folder($ops) {
	foreach ($ops['return'] as $index => $table) {
		if ($table['table'] !== 'media') continue;
		if ($ops['record_diff'][$index]['filename'] !== 'diff') continue;
		if ($ops['record_new'][$index]['filetype_id'] !== wrap_id('filetypes', 'folder')) continue;
		
		$data = [$ops['record_new'][$index]['medium_id'] => $ops['record_new'][$index]['medium_id']];
		$sql = 'SELECT medium_id, main_medium_id
			FROM /*_PREFIX_*/media
			WHERE main_medium_id IN (%s)';
		$children = wrap_db_children($data, $sql);
		foreach ($children as $medium_id)
			zzform_update('media', ['medium_id' => $medium_id]);
	}
}
