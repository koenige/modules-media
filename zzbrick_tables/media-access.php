<?php 

/**
 * media module
 * Table definition for 'media access'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Media Access Rights';
$zz['table'] = '/*_PREFIX_*/media_access';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'medium_access_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Folder';
$zz['fields'][2]['field_name'] = 'medium_id';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = sprintf('SELECT /*_PREFIX_*/media.medium_id
		, /*_PREFIX_*/media.filename
	FROM /*_PREFIX_*/media 
	WHERE /*_PREFIX_*/media.filetype_id = %d
	ORDER BY /*_PREFIX_*/media.filename', wrap_filetype_id('folder'));
$zz['fields'][2]['sql_character_set'][1] = 'utf8';
$zz['fields'][2]['sql_character_set'][2] = 'utf8';
$zz['fields'][2]['id_field_name'] = '/*_PREFIX_*/media.medium_id';
$zz['fields'][2]['display_field'] = 'filename';
$zz['fields'][2]['exclude_from_search'] = true;

$zz['fields'][3]['field_name'] = 'usergroup_id';
$zz['fields'][3]['type'] = 'select';
$zz['fields'][3]['sql'] = 'SELECT usergroup_id, usergroup 
	FROM /*_PREFIX_*/usergroups
	ORDER BY usergroup';
$zz['fields'][3]['display_field'] = 'usergroup';
$zz['fields'][3]['character_set'] = 'utf8';

$zz['fields'][4]['title'] = 'Access';
$zz['fields'][4]['field_name'] = 'access_category_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = 'SELECT categories.category_id
		, categories.category, main_category_id
	FROM categories
	ORDER BY category';
$zz['fields'][4]['display_field'] = 'category';
$zz['fields'][4]['search'] = '/*_PREFIX_*/categories.category';
$zz['fields'][4]['show_hierarchy'] = 'main_category_id';
$zz['fields'][4]['show_hierarchy_subtree'] = wrap_category_id('access');

$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;


$zz['sql'] = 'SELECT /*_PREFIX_*/media_access.*
		, /*_PREFIX_*/usergroups.usergroup
		, /*_PREFIX_*/categories.category
		, /*_PREFIX_*/media.filename
	FROM /*_PREFIX_*/media_access
	LEFT JOIN /*_PREFIX_*/media USING (medium_id)
	LEFT JOIN /*_PREFIX_*/usergroups USING (usergroup_id)
	LEFT JOIN /*_PREFIX_*/categories
		ON /*_PREFIX_*/media_access.access_category_id = /*_PREFIX_*/categories.category_id
';
$zz['sqlorder'] = ' ORDER BY medium_access_id';
