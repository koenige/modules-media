<?php

/**
 * Zugzwang Project
 * Output linked documents
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * linked documents
 *
 * @param array $params
 */
function page_docs(&$params, $page) {
	if (empty($page['media']['links'])) return '';
	
	$text = wrap_template('docs', $page['media']['links']);
	return $text;
}
