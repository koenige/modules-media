<?php

/**
 * media module
 * gallery
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
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
	
	$category_id = wrap_category_id('tags/'.$params[0]);
	if (!$category_id) return $page;

	$media = wrap_get_media($category_id, 'media_categories categories', 'category');
	if (!$media) return $page;

	$page['text'] = wrap_template('images', $media['images']);
	return $page;
}
