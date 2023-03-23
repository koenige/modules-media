<?php

/**
 * media module
 * Output single download
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * single documents
 *
 * @param array $params
 */
function page_download(&$params, $page) {
	wrap_include_files('request', 'zzbrick');

	if (empty($page['status'])) {
		$text = '%%% page download '.implode(' ', $params).' %%%';
	} elseif (!empty($page['media'])) {
		array_unshift($params, 'download');
		$text = brick_request_link($page['media'], $params, 'sequence');
	} else {
		// if an arror occured on the page
		$text = '';
	}
	$params = []; // no formatting!
	return $text;
}
