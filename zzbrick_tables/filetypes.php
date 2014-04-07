<?php 

/**
 * Zugzwang Project
 * Table definition for 'filetypes'
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2005-2012, 2014 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Filetypes';
$zz['table'] = '/*_PREFIX_*/filetypes';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'filetype_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][6]['field_name'] = 'filetype';
$zz['fields'][6]['type'] = 'text';
$zz['fields'][6]['list_prefix'] = '<strong>';
$zz['fields'][6]['list_suffix'] = '</strong>';
$zz['fields'][6]['class'] = 'block480a';

$zz['fields'][4]['title'] = 'Description';
$zz['fields'][4]['field_name'] = 'filetype_description';
$zz['fields'][4]['list_append_next'] = true;
$zz['fields'][4]['list_suffix'] = '<br>';
$zz['fields'][4]['class'] = 'block480';

$zz['fields'][2]['title'] = 'MIME Content Type';
$zz['fields'][2]['field_name'] = 'mime_content_type';
$zz['fields'][2]['type'] = 'text';
$zz['fields'][2]['list_append_next'] = true;
$zz['fields'][2]['list_append_show_title'] = true; 
$zz['fields'][2]['list_prefix'] = '<em>'; 

$zz['fields'][3]['title'] = 'MIME Subtype';
$zz['fields'][3]['field_name'] = 'mime_subtype';
$zz['fields'][3]['list_prefix'] = '/';
$zz['fields'][3]['list_suffix'] = '</em>'; 

$zz['fields'][7]['title_tab'] = 'Ext.';
$zz['fields'][7]['field_name'] = 'extension';
$zz['fields'][7]['type'] = 'text';
$zz['fields'][7]['prefix'] = '.';
$zz['fields'][7]['list_prefix'] = '.';
$zz['fields'][7]['null_string'] = true;
$zz['fields'][7]['class'] = 'hidden480';

$zz['fields'][5]['title'] = 'Count';
$zz['fields'][5]['field_name'] = 'count_files';
$zz['fields'][5]['type'] = 'display';
$zz['fields'][5]['class'] = 'number';
$zz['fields'][5]['exclude_from_search'] = true;
$zz['fields'][5]['class'] = 'hidden480';

$zz['sql'] = 'SELECT /*_PREFIX_*/filetypes.*
		, (SELECT COUNT(medium_id) FROM /*_PREFIX_*/media
			WHERE /*_PREFIX_*/media.filetype_id = /*_PREFIX_*/filetypes.filetype_id)
			AS count_files
	FROM /*_PREFIX_*/filetypes';
$zz['sqlorder'] = ' ORDER BY mime_content_type, mime_subtype';
