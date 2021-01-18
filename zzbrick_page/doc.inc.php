<?php

/**
 * Zugzwang Project
 * Output single document
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * single documents
 *
 * @param array $params
 */
function page_doc(&$params, $page) {
	global $zz_setting;
	require_once $zz_setting['lib'].'/zzbrick/request.inc.php';

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
