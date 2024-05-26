<?php

/**
 * media module
 * Output single document
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021, 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * single documents
 *
 * @param array $params
 */
function page_doc(&$params, $page) {
	wrap_include('request', 'zzbrick');

	if (empty($page['status'])) {
		$text = '%%% page doc '.implode(' ', $params).' %%%';
	} elseif (!empty($page['media'])) {
		array_unshift($params, 'doc');
		$text = brick_request_link($page['media'], $params, 'sequence');
	} else {
		// if an arror occured on the page
		$text = '';
	}
	$params = []; // no formatting!
	return $text;
}
