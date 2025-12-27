<?php

/**
 * media module
 * gallery
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show a gallery
 *
 * @param array $params URL as given by zzwrap (language, identifier parts)
 *		[0] identifier of tag
 * @return array
 */
function mod_media_gallery($params) {
	$page['text'] = "\n"; // no 404
	if (count($params) !== 1) return $page;
	
	$where = [];
	// media associated with the gallery tag, showing contents of a folder
	if (wrap_category_id('tags/gallery', 'check')) {
		$sql = 'SELECT medium_id
			FROM /*_PREFIX_*/media
			WHERE filename = "%s"';
		$sql = sprintf($sql, wrap_db_escape($params[0]));
		$folder_medium_id = wrap_db_fetch($sql, '', 'single value');
		if ($folder_medium_id) {
			$where[] = sprintf('main_medium_id = %d', $folder_medium_id);
			$params[0] = 'gallery';
		}
	}

	// all media associated with a tag	
	$category_id = wrap_category_id('tags/'.$params[0]);
	if (!$category_id) return $page;

	$media = wrap_get_media($category_id, 'categories', 'category', $where);
	if (!$media) return $page;

	if (wrap_package('magnificpopup'))
		$page['extra']['magnific_popup'] = true;
	$page['text'] = wrap_template('images', $media['images']);
	return $page;
}
