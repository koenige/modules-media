<?php 

/**
 * Zugzwang Project
 * Output single image
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * single image
 * 
 * @param array $params (HTML-Code, if value will be returned)
 * @param array $page
 * @return string $text
 */
function page_image(&$params, &$page) {
	global $zz_setting;
	require_once $zz_setting['lib'].'/zzbrick/request.inc.php';

	if (empty($page)) {
		$text = '%%% page image '.implode(' ', $params).' %%%';
	} else {
		array_unshift($params, 'image');
		$text = brick_request_link($page['media'], $params, 'sequence');
	}
	$params = []; // no formatting!
	return $text;
}
