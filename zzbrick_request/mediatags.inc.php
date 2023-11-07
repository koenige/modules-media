<?php 

/**
 * media module
 * overview of tags for media
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * overview of tags for media
 *
 * @param array $params
 * @return array
 */
function mod_media_mediatags($params) {
	$suffix = str_ends_with($params[0], '/-') ? '/-' : '';
	$sql = 'SELECT DISTINCT categories.category_id, categories.category
			, CONCAT("-", IFNULL(SUBSTRING_INDEX(SUBSTRING_INDEX(types.parameters, "&alias=", -1), "&", 1), types.path), "/", SUBSTRING_INDEX(categories.path, "/", -1), "%s") AS path
			, COUNT(medium_id) AS media_count
		FROM /*_PREFIX_*/media_categories
		LEFT JOIN /*_PREFIX_*/categories categories USING (category_id)
		LEFT JOIN /*_PREFIX_*/categories types
			ON /*_PREFIX_*/media_categories.type_category_id = types.category_id
		GROUP BY categories.category_id, types.category_id
		HAVING media_count > 0
	';
	$sql = sprintf($sql, $suffix);
	$data = wrap_db_fetch($sql, 'category_id');
	$data = wrap_translate($data, 'categories');
	
	$page['text'] = wrap_template('mediatags', $data);
	$page['title'] = sprintf('%s<br><small><a href="../%s">%s</a> / %s</small>'
		, wrap_text('Media Pool'), $suffix ? '../-/' : '', wrap_text('TOP'), wrap_text('Tags')
	);
	return $page;
}
