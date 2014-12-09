<?php 

/**
 * Zugzwang Project
 * Table definition for 'media', including folders
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2014 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['table'] = '/*_PREFIX_*/media';

// @todo put into language module
$language_code = $zz_conf['language'];
$possible_codes = array('en', 'fr', 'de');
if (!in_array($language_code, $possible_codes)) {
	$language_code = $possible_codes[0];
}

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'medium_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][8]['title'] = 'Folder';
$zz['fields'][8]['field_name'] = 'main_medium_id';
$zz['fields'][8]['type'] = 'select';
$zz['fields'][8]['sql'] = 'SELECT medium_id, title, filename, main_medium_id
	FROM /*_PREFIX_*/media
	LEFT JOIN /*_PREFIX_*/filetypes USING (filetype_id)
	WHERE filetype = "folder"
	ORDER BY ISNULL(/*_PREFIX_*/media.sequence),
	/*_PREFIX_*/media.sequence, /*_PREFIX_*/media.date, time, title ASC';
$zz['fields'][8]['sql_ignore'] = 'filename';
$zz['fields'][8]['hide_in_list'] = true;
$zz['fields'][8]['show_hierarchy'] = 'main_medium_id';
$zz['fields'][8]['show_hierarchy_same_table'] = true;
$zz['fields'][8]['if']['where']['hide_in_form'] = true;

$zz['fields'][14]['title'] = 'Medium';
$zz['fields'][14]['field_name'] = 'image';
$zz['fields'][14]['type'] = 'upload_image';
$zz['fields'][14]['class'] = 'medium';
if (empty($brick['local_settings']['no_publish'])) {
	$zz['fields'][14]['class'] = 'medium published';
	$zz['fields'][14]['if'][1]['class'] = 'medium unpublished';
}
$zz['fields'][14]['unless'][2]['link'] =  array(
	'root' => $zz_setting['media_folder'], 
	'webroot' => $zz_setting['files_path'],
	'string1' => '/',
	'field1' => 'filename',
	'string2' => '.',
	'string3' => 'master',
	'string4' => '.',
	'extension' => 'master_extension',
	'webstring1' => '?v=',
	'webfield1' => 'version'
);
$zz['fields'][14]['path'] = array(
	'root' => $zz_setting['media_folder'], 
	'webroot' => $zz_setting['files_path'],
	'string1' => '/',
	'field1' => 'filename',
	'string2' => '.',
	'string3' => $zz_setting['media_sizes']['min']['path'],
	'string4' => '.',
	'extension' => 'thumb_extension',
	'webstring1' => '?v=',
	'webfield1' => 'version'
);
$zz['fields'][14]['default_image'] = '/_layout/media/no-preview.png';
$zz['fields'][14]['input_filetypes'] = array_keys(wrap_db_fetch('SELECT filetype 
FROM /*_PREFIX_*/filetypes ORDER BY filetype', 'filetype'));

$zz['fields'][14]['if'][2]['hide_in_form'] = true;

// Master file
$zz['fields'][14]['image'][0]['title'] = 'master';
$zz['fields'][14]['image'][0]['field_name'] = 'master';
$zz['fields'][14]['image'][0]['path'] = $zz['fields'][14]['path'];
$zz['fields'][14]['image'][0]['path']['string3'] = 'master';
$zz['fields'][14]['image'][0]['path']['extension'] = 'master_extension';
$zz['fields'][14]['image'][0]['required'] = true;

$i = 1;
foreach ($zz_setting['media_sizes'] as $title => $size) {
	$zz['fields'][14]['image'][$i]['title'] = $title;
	$zz['fields'][14]['image'][$i]['path'] = $zz['fields'][14]['path'];
	$zz['fields'][14]['image'][$i]['path']['string3'] = $size['path'];
	$zz['fields'][14]['image'][$i]['width'] = $size['width'];
	$zz['fields'][14]['image'][$i]['height'] = $size['height'];
	$zz['fields'][14]['image'][$i]['action'] = $size['action'];
	$zz['fields'][14]['image'][$i]['source'] = 0;
	$zz['fields'][14]['image'][$i]['recreate_on_change'] = array(16);
	$i++;
}

$zz['fields'][14]['if'][2]['type'] = 'image';
$zz['fields'][14]['if'][2]['path'] = array (
	'string1' => '/_layout/media/folder-'.$zz_setting['media_sizes']['min']['path'].'.png',
	'ignore_record' => true
);
$zz['fields'][14]['if'][2]['default_image'] = '/_layout/media/folder-'.$zz_setting['media_sizes']['min']['path'].'.png';
$zz['fields'][14]['if'][2]['class'] = 'folder';

$zz['fields'][16]['title'] = 'Thumbnail';
$zz['fields'][16]['field_name'] = 'thumb_filetype_id';
$zz['fields'][16]['type'] = 'select';
$zz['fields'][16]['sql'] = sprintf('SELECT filetype_id, UCASE(filetype)
		, (CASE mime_subtype
			WHEN "jpeg" THEN "%s"
			WHEN "png" THEN "%s"
			WHEN "gif" THEN "%s"
		END) as explanation
	FROM /*_PREFIX_*/filetypes
	WHERE mime_content_type = "image"
	AND mime_subtype IN ("jpeg", "gif", "png")
	ORDER BY IF(mime_subtype = "jpeg", 0, 1), IF(mime_subtype = "png", 0, 1)',
	wrap_text('good for photos'),
	wrap_text('good for drawings & texts / transparency'),
	wrap_text('good for drawings & texts / transparency (deprecated)')
);
$zz['fields'][16]['path_sql'] = 'SELECT extension
	FROM /*_PREFIX_*/filetypes WHERE filetype_id = ';
$zz['fields'][16]['concat_fields'] = ' – ';
$zz['fields'][16]['display_field'] = 'thumb_extension';
$zz['fields'][16]['default'] = 1; // image/jpeg
$zz['fields'][16]['hide_in_list'] = true;
$zz['fields'][16]['search'] = 't_mime.extension';
$zz['fields'][16]['separator'] = true;
$zz['fields'][16]['if'][2] = false;
$zz['fields'][16]['show_values_as_list'] = true;

$zz['fields'][9]['title'] = 'Published?';
$zz['fields'][9]['title_tab'] = 'WWW?';
$zz['fields'][9]['field_name'] = 'published';
$zz['fields'][9]['type'] = 'select';
$zz['fields'][9]['enum'] = array('yes', 'no');
$zz['fields'][9]['hide_in_list'] = true;
if (empty($brick['local_settings']['no_publish'])) {
	$zz['fields'][9]['default'] = 'yes';
} else {
	$zz['fields'][9]['value'] = 'no';
	$zz['fields'][9]['hide_in_form'] = true;
}

$zz['fields'][2]['field_name'] = 'title';
$zz['fields'][2]['upload_field'] = 14;
$zz['fields'][2]['upload_value'] = 'title';
$zz['fields'][2]['unless'][2]['explanation'] = 'The filename will be used as a default if nothing is entered.';
$zz['fields'][2]['class'] = 'legend';

$zz['fields'][3]['field_name'] = 'description';
$zz['fields'][3]['type'] = 'memo';
$zz['fields'][3]['format'] = 'markdown';
$zz['fields'][3]['hide_in_list'] = true;

$zz['fields'][6] = false; // media_categories

if (empty($brick['local_settings']['no_sequence'])) {
	$zz['fields'][33]['title_tab'] = 'Seq.';
	$zz['fields'][33]['field_name'] = 'sequence';
	$zz['fields'][33]['type'] = 'number';
	$zz['fields'][33]['list_append_next'] = true;
	$zz['fields'][33]['list_suffix'] = ' &#8211; ';
}

if (!empty($zz_setting['languages_allowed']) AND count($zz_setting['languages_allowed']) > 1) {
	$zz['fields'][24]['field_name'] = 'language_id';
	$zz['fields'][24]['type'] = 'select';
	$zz['fields'][24]['default'] = $zz_setting['language_ids'][$zz_conf['language']];
	$zz['fields'][24]['hide_in_list'] = true;
	$zz['fields'][24]['sql'] = sprintf('SELECT language_id, language_%s
		FROM /*_PREFIX_*/languages
		WHERE website = "yes"
		ORDER BY language_%s', $language_code, $language_code);
	$zz['fields'][24]['display_field'] = sprintf('language_%s', $language_code);
}

$zz['fields'][4]['field_name'] = 'date';
$zz['fields'][4]['type'] = 'date';
$zz['fields'][4]['upload_field'] = 14;
$zz['fields'][4]['upload_value'] = 'exif[DateTimeOriginal]';
$zz['fields'][4]['explanation'] = 'Digital photography: If nothing is entered, date and time will be read from file.';
$zz['fields'][4]['list_prefix'] = '<em>';
$zz['fields'][4]['list_suffix'] = '</em>';
$zz['fields'][4]['append_next'] = true;
$zz['fields'][4]['if'][2]['hide_in_form'] = true;

$zz['fields'][5]['field_name'] = 'time';
$zz['fields'][5]['type'] = 'time';
$zz['fields'][5]['prefix'] = ' '.wrap_text('at').' ';
$zz['fields'][5]['suffix'] = ' '.wrap_text('h');
$zz['fields'][5]['upload_field'] = 14;
$zz['fields'][5]['upload_value'] = 'exif[DateTimeOriginal]';
$zz['fields'][5]['hide_in_list'] = true;
$zz['fields'][5]['if'][2] = false;

if (empty($brick['local_settings']['no_publish'])) {
	$zz['fields'][19]['field_name'] = 'source';
	$zz['fields'][19]['hide_in_list'] = true;
	$zz['fields'][19]['explanation'] = 'If it\'s not a medium created by yourself, who created it, where did you find it?';
	$zz['fields'][19]['if'][2] = false;
	$zz['fields'][19]['separator'] = true;
	$zz['fields'][19]['if']['add']['separator'] = false;
} else {
	$zz['fields'][5]['separator'] = true;
	$zz['fields'][5]['if']['add']['separator'] = false;
}

$zz['fields'][10]['field_name'] = 'filename';
$zz['fields'][10]['type'] = 'identifier';
$zz['fields'][10]['fields'] = array('main_medium_id[filename]', 'title');
$zz['fields'][10]['class'] = 'hidden';
$zz['fields'][10]['hide_in_list'] = true;
$zz['fields'][10]['conf_identifier']['concat'] = '/';	
$zz['fields'][10]['conf_identifier']['exists'] = '-';	

$zz['fields'][15]['title'] = 'Filetype';
$zz['fields'][15]['title_append'] = 'File';
$zz['fields'][15]['field_name'] = 'filetype_id';
$zz['fields'][15]['type'] = 'hidden';
$zz['fields'][15]['upload_field'] = 14;
$zz['fields'][15]['upload_value'] = 'filetype';
$zz['fields'][15]['upload_sql'] = 'SELECT filetype_id
	FROM /*_PREFIX_*/filetypes 
	WHERE filetype = ';
$zz['fields'][15]['display_field'] = 'master_extension_ucase';
$zz['fields'][15]['hide_in_list'] = true;
$zz['fields'][15]['search'] = 'o_mime.extension';
$zz['fields'][15]['dont_show_missing'] = true;
$zz['fields'][15]['append_next'] = true;
$zz['fields'][15]['suffix'] = ', ';
$zz['fields'][15]['if']['add']['hide_in_form'] = true;
$zz['fields'][15]['if'][2]['append_next'] = false;
$zz['fields'][15]['if'][2]['hide_in_form'] = true;

$zz['fields'][26]['title'] = 'Filesize';
$zz['fields'][26]['field_name'] = 'filesize';
$zz['fields'][26]['type'] = 'hidden';
$zz['fields'][26]['unit'] = ' bytes';
$zz['fields'][26]['upload_field'] = 14;
$zz['fields'][26]['upload_value'] = 'size';
$zz['fields'][26]['hide_in_list'] = true;
$zz['fields'][26]['dont_show_missing'] = true;
$zz['fields'][26]['if']['add']['hide_in_form'] = true;
$zz['fields'][26]['if'][2] = false;

$zz['fields'][34]['title'] = 'MD5';
$zz['fields'][34]['field_name'] = 'md5_hash';
$zz['fields'][34]['type'] = 'hidden';
$zz['fields'][34]['hide_in_list'] = true;
$zz['fields'][34]['upload_field'] = 14;
$zz['fields'][34]['upload_value'] = 'md5';
$zz['fields'][34]['dont_show_missing'] = true;
$zz['fields'][34]['if']['add']['hide_in_form'] = true;
$zz['fields'][34]['if'][2] = false;

$zz['fields'][35]['field_name'] = 'version';
$zz['fields'][35]['type'] = 'hidden';
$zz['fields'][35]['hide_in_list'] = true;
$zz['fields'][35]['upload_field'] = 14;
$zz['fields'][35]['upload_value'] = 'increment_on_change';
$zz['fields'][35]['dont_show_missing'] = true;
$zz['fields'][35]['if']['add']['hide_in_form'] = true;
$zz['fields'][35]['if'][2] = false;

$zz['fields'][20]['title'] = 'Updated';
$zz['fields'][20]['field_name'] = 'last_update';
$zz['fields'][20]['type'] = 'timestamp';
$zz['fields'][20]['hide_in_list'] = true;

$zz['sql'] = sprintf('SELECT /*_PREFIX_*/media.*
	, CONCAT(DATE_FORMAT(/*_PREFIX_*/media.date, "%%Y"), "/", 
		DATE_FORMAT(/*_PREFIX_*/media.date, "%%m"), "/", 
		DATE_FORMAT(/*_PREFIX_*/media.date, "%%d")) AS date_path
	, o_mime.extension AS master_extension
	, UCASE(o_mime.extension) AS master_extension_ucase
	, t_mime.extension AS thumb_extension
	, language_%s
	FROM /*_PREFIX_*/media
	LEFT JOIN /*_PREFIX_*/languages USING (language_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS o_mime
		ON /*_PREFIX_*/media.filetype_id = o_mime.filetype_id
	LEFT JOIN /*_PREFIX_*/filetypes AS t_mime
		ON /*_PREFIX_*/media.thumb_filetype_id = t_mime.filetype_id
', $language_code);
$zz['sqlorder'] = ' ORDER BY ISNULL(/*_PREFIX_*/media.sequence),
	/*_PREFIX_*/media.sequence, /*_PREFIX_*/media.date, time, title ASC';

if (empty($brick['local_settings']['no_publish'])) {
	$zz['conditions'][1]['scope'] = 'record';
	$zz['conditions'][1]['where'] = '/*_PREFIX_*/media.published = "no"';
}

$zz['conditions'][2]['scope'] = 'record';
$zz['conditions'][2]['where'] = sprintf('o_mime.filetype_id = %d',
	$zz_setting['filetype_ids']['folder']);
$zz['conditions'][2]['add']['sql'] = 'SELECT filetype_id
	FROM /*_PREFIX_*/filetypes o_mime
	WHERE filetype_id = ';
$zz['conditions'][2]['add']['key_field_name'] = 'filetype_id';

$zz['title'] = wrap_text('Media Pool');

$zz_conf['max_select'] = 100;
$zz_conf['limit'] = 42;

if (empty($brick['local_settings']['no_publish'])) {
	$zz_conf['footer_text'] .= '<p><em>'.wrap_text('Coloured border: medium is published; gray border: medium is not published.').'</em></p>';
}
$zz_conf['list_display'] = 'ul';

$zz_conf['add'] = array();
$zz_conf['add'][] = array(
	'type' => wrap_text('File'),
	'field_name' => 'filetype_id',
	'value' => ''
);
$zz_conf['add'][] = array(
	'type' => wrap_text('Folder'),
	'field_name' => 'filetype_id',
	'value' => $zz_setting['filetype_ids']['folder']
);

$zz['page']['head'] = "\t".'<link rel="stylesheet" type="text/css" href="'.$zz_setting['layout_path'].'/media/zzform-media.css">'."\n";
