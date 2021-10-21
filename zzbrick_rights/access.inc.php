<?php 

/**
 * media module
 * Access functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check accces rights for a path
 *
 * @param array $path
 * @return array
 */
function mod_media_access($path) {
	$sql_tpl = 'SELECT usergroup, categories.parameters, category
		FROM media_access
		LEFT JOIN media USING (medium_id)
		LEFT JOIN usergroups USING (usergroup_id)
		LEFT JOIN categories
			ON media_access.access_category_id = categories.category_id
		WHERE filename = "%s"';

	$access = [];
	while ($path) {
		$sql = sprintf($sql_tpl, implode('/', $path));
		$folder_access = wrap_db_fetch($sql, 'usergroup');
		foreach ($folder_access as $usergroup => $rights) {
			// more granular rights exist? continue
			if (array_key_exists($usergroup, $access)) continue;
			$access[$usergroup] = $rights;
		}
		array_pop($path);
	}
	if (!$access) return [];

	$my_access = [];
	foreach ($access as $usergroup => $rights) {
		if (!brick_access_rights($usergroup)) continue;
		parse_str($rights['parameters'], $parameters);
		$my_access += $parameters;
	}
	return $my_access;
}
