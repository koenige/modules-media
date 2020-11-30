<?php 

/**
 * Zugzwang Project
 * Output single image on top of page
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * top image
 * 
 * @param array $params (HTML-Code, if value will be returned)
 * @param array $page
 * @return string $text
 */
function page_topimage($params, &$page) {
	global $zz_setting;
	// different image for error pages, if set
	if ($page['status'] !== 200
		AND $page_id = wrap_get_setting('media_topimage_error_page_id')
	) {
		$page['media'] = wrap_get_media($page_id);
	}
	if (empty($page['media']['images'])) return '';

	// check if image with sequence 1 still has not been distributed
	reset($page['media']['images']);
	$medium = key($page['media']['images']);
	$image = $page['media']['images'][$medium];
	if ($image['sequence'].'' !== '1') return '';

	// do not display twice
	array_shift($page['media']['images']);

	// set defaults
	if (empty($image['path'])) $image['path'] = wrap_get_setting('media_standard_image_size');
	if (empty($image['path_x2'])) $image['path_x2'] = wrap_get_setting('media_standard_image_size_x2');
	if (empty($image['path_x2']) AND is_numeric($image['path'])) {
		foreach ($zz_setting['media_sizes'] as $size) {
			if ($size['path'] != 2 * $image['path']) continue;
			$image['path_x2'] = $size['path'];
		}
	}
	if (empty($image['position'])) $image['position'] = 'left';

	$text = wrap_template('image', $image);
	return $text;
}
