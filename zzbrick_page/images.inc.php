<?php 

/**
 * Zugzwang Project
 * Output image gallery
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014, 2017, 2019-2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * image gallery
 * 
 * @param array $params (HTML-Code, if value will be returned)
 * @param array $page
 * @return string $text
 */
function page_images($params, $page) {
	if (empty($page['media']['images'])) return '';
	$text = wrap_template('images', $page['media']['images']);
	return $text;
}
