<?php 

/**
 * media module
 * overview of tags for media
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023, 2026 Gustaf Mossakowski
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
	
	// get media count
	$sql = 'SELECT category_id, COUNT(medium_id) AS media_count
		FROM /*_PREFIX_*/media_categories
		GROUP BY category_id
		HAVING media_count > 0';
	$category_count = wrap_db_fetch($sql, 'category_id');
	
	if ($category_count) {
		$sql = 'SELECT DISTINCT categories.category_id, categories.category
				, CONCAT(
					"-", IFNULL(SUBSTRING_INDEX(SUBSTRING_INDEX(types.parameters, "&alias=", -1), "&", 1), types.path),
					"/", SUBSTRING(categories.path, INSTR(categories.path, "/") + 1), "%s"
				) AS path
			FROM /*_PREFIX_*/media_categories
			LEFT JOIN /*_PREFIX_*/categories USING (category_id)
			LEFT JOIN /*_PREFIX_*/categories types
				ON /*_PREFIX_*/media_categories.type_category_id = types.category_id
			WHERE /*_PREFIX_*/categories.category_id IN (%s)
		';
		$sql = sprintf($sql, $suffix, implode(',', array_keys($category_count)));
		$data = wrap_db_fetch($sql, 'category_id');
		$data = wrap_translate($data, 'categories');
		foreach ($category_count as $category_id => $category)
			$data[$category_id]['media_count'] = $category['media_count'];
	}

	$data['add_new_path'] = wrap_path('default_tables', 'categories');
	
	$page['text'] = wrap_template('mediatags', $data);
	$page['title'] = sprintf('%s<br><small><a href="../%s">%s</a> / %s</small>'
		, wrap_text('Media Pool'), $suffix ? '../-/' : '', wrap_text('TOP'), wrap_text('Tags')
	);
	return $page;
}
