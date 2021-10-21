<?php 

/**
 * media module
 * Table definition for 'media', including folders
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2018, 2020-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['table'] = '/*_PREFIX_*/media';

// @todo put into language module
$language_code = $zz_setting['lang'];
$possible_codes = ['en', 'fr', 'de'];
if (!in_array($language_code, $possible_codes)) {
	$language_code = $possible_codes[0];
}

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'medium_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][8]['title'] = 'Folder';
$zz['fields'][8]['field_name'] = 'main_medium_id';
$zz['fields'][8]['type'] = 'select';
$zz['fields'][8]['sql'] = 'SELECT medium_id, filename, main_medium_id
	FROM /*_PREFIX_*/media
	LEFT JOIN /*_PREFIX_*/filetypes USING (filetype_id)
	WHERE filetype = "folder"
	ORDER BY ISNULL(/*_PREFIX_*/media.sequence),
	/*_PREFIX_*/media.sequence, /*_PREFIX_*/media.date, time, title ASC';
$zz['fields'][8]['key_field_name'] = 'medium_id';
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
$zz['fields'][14]['unless'][2]['link'] = [
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
];
$zz['fields'][14]['path'] = [
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
];
$zz['fields'][14]['default_image'] = $zz_setting['layout_path'].'/media/no-preview.png';
$zz['fields'][14]['input_filetypes'] = array_keys(wrap_db_fetch('SELECT filetype 
FROM /*_PREFIX_*/filetypes ORDER BY filetype', 'filetype'));

$zz['fields'][14]['if'][2]['hide_in_form'] = true;
$zz['fields'][14]['if'][3]['hide_in_form'] = true;

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
	unset($size['path']);
	$zz['fields'][14]['image'][$i] += $size;
	if (!isset($zz['fields'][14]['image'][$i]['width']))
		$zz['fields'][14]['image'][$i]['width'] = false;
	if (!isset($zz['fields'][14]['image'][$i]['height']))
		$zz['fields'][14]['image'][$i]['height'] = false;
	if (!isset($zz['fields'][14]['image'][$i]['source']))
		$zz['fields'][14]['image'][$i]['source'] = 0;
	$zz['fields'][14]['image'][$i]['use_modified_source'] = !empty($zz['fields'][14]['image'][$i]['source']) ? true : false;
	$zz['fields'][14]['image'][$i]['no_action_unless_thumb_extension'] = true;
	$zz['fields'][14]['image'][$i]['recreate_on_change'] = [16, 36];
	if ($size['action'] === 'crop') {
		$zz['fields'][14]['image'][$i]['options'] = [36];
	}
	$i++;
}

$zz['fields'][14]['if'][2]['type'] = 'image';
$zz['fields'][14]['if'][2]['path'] = [
	'string1' => $zz_setting['layout_path'].'/media/folder-'.$zz_setting['media_sizes']['min']['path'].'.png',
	'ignore_record' => true
];
$zz['fields'][14]['if'][2]['default_image'] = $zz_setting['layout_path'].'/media/folder-'.$zz_setting['media_sizes']['min']['path'].'.png';
$zz['fields'][14]['if'][2]['class'] = 'folder';

$zz['fields'][14]['if'][3]['type'] = 'image';
$zz['fields'][14]['if'][3]['path'] = [
	'string1' => $zz_setting['layout_path'].'/media/embed-'.$zz_setting['media_sizes']['min']['path'].'.png',
	'ignore_record' => true
];
$zz['fields'][14]['if'][3]['default_image'] = $zz_setting['layout_path'].'/media/embed-'.$zz_setting['media_sizes']['min']['path'].'.png';

$zz['fields'][16]['title'] = 'Thumbnail';
$zz['fields'][16]['field_name'] = 'thumb_filetype_id';
$zz['fields'][16]['key_field_name'] = 'filetype_id';
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
$zz['fields'][16]['hide_novalue'] = false;
$zz['fields'][16]['search'] = 't_mime.extension';
$zz['fields'][16]['separator'] = true;
$zz['fields'][16]['if'][2] = false;
$zz['fields'][16]['if'][3] = false;
$zz['fields'][16]['show_values_as_list'] = true;
$zz['fields'][16]['character_set'] = 'latin1';

$zz['fields'][9]['title'] = 'Published?';
$zz['fields'][9]['title_tab'] = '<abbr title="Publish on Website?">Web?</abbr>';
$zz['fields'][9]['field_name'] = 'published';
$zz['fields'][9]['type'] = 'select';
$zz['fields'][9]['enum'] = ['yes', 'no'];
$zz['fields'][9]['hide_in_list'] = true;
if (empty($brick['local_settings']['no_publish'])) {
	$zz['fields'][9]['default'] = 'yes';
} else {
	$zz['fields'][9]['value'] = 'no';
	$zz['fields'][9]['hide_in_form'] = true;
}
$zz['fields'][9]['if'][3] = false;

$zz['fields'][2]['field_name'] = 'title';
$zz['fields'][2]['upload_field'] = 14;
$zz['fields'][2]['upload_value'] = 'title';
$zz['fields'][2]['explanation'] = 'The filename will be used as a default if nothing is entered.';
$zz['fields'][2]['if'][2]['explanation'] = '';
$zz['fields'][2]['if'][3]['explanation'] = '';
$zz['fields'][2]['class'] = 'legend block480a';
$zz['fields'][2]['if'][3]['title'] = 'Code';
$zz['fields'][2]['if'][3]['type'] = 'write_once';
$zz['fields'][2]['typo_cleanup'] = true;

$zz['fields'][3]['field_name'] = 'description';
$zz['fields'][3]['type'] = 'memo';
$zz['fields'][3]['format'] = 'markdown';
$zz['fields'][3]['hide_in_list'] = true;
$zz['fields'][3]['typo_cleanup'] = true;

$zz['fields'][17]['field_name'] = 'alternative_text';
$zz['fields'][17]['type'] = 'text';
$zz['fields'][17]['explanation'] = 'optional content of `alt`-attribute for images, for screen reader software';
$zz['fields'][17]['hide_in_list'] = true;

// additional fields
$zz['fields'][6] = false; // media_categories
$zz['fields'][41] = false;
$zz['fields'][42] = false;
$zz['fields'][43] = false;
$zz['fields'][44] = false;

$crop = false;
foreach ($zz_setting['media_sizes'] as $size) {
	if ($size['action'] === 'crop') $crop = true;
}
if ($crop) {
	$zz['fields'][36]['field_name'] = 'clipping';
	$zz['fields'][36]['explanation'] = 'Position of clipping of cropped image';
	$zz['fields'][36]['type'] = 'select';
	$zz['fields'][36]['enum'] = ['center', 'top', 'right', 'bottom', 'left'];
	$zz['fields'][36]['default'] = 'center';
	$zz['fields'][36]['hide_in_list'] = true;
	$zz['fields'][36]['if'][2]['hide_in_form'] = true;
	$zz['fields'][36]['if'][3]['hide_in_form'] = true;
	$zz['fields'][36]['options'] = [
		'center' => ['action' => 'crop_center'],
		'top' => ['action' => 'crop_top'],
		'right' => ['action' => 'crop_right'],
		'bottom' => ['action' => 'crop_bottom'],
		'left' => ['action' => 'crop_left']
	];
}

if (empty($brick['local_settings']['no_sequence'])) {
	$zz['fields'][33]['title_tab'] = 'Seq.';
	$zz['fields'][33]['field_name'] = 'sequence';
	$zz['fields'][33]['type'] = 'number';
	$zz['fields'][33]['list_append_next'] = true;
	$zz['fields'][33]['list_suffix'] = ' – ';
	$zz['fields'][33]['class'] = 'hidden480';
	$zz['fields'][33]['hide_in_list_if_empty'] = true;
	$zz['fields'][33]['if'][3] = false;
	
	$zz['fields'][31] = [];
}

if (!empty($zz_setting['languages_allowed']) AND count($zz_setting['languages_allowed']) > 1) {
	$zz['fields'][24]['field_name'] = 'language_id';
	$zz['fields'][24]['type'] = 'select';
	$zz['fields'][24]['default'] = wrap_language_id($zz_setting['lang']);
	$zz['fields'][24]['hide_in_list'] = true;
	$zz['fields'][24]['sql'] = sprintf('SELECT language_id, language_%s
		FROM /*_PREFIX_*/languages
		WHERE website = "yes"
		ORDER BY language_%s', $language_code, $language_code);
	$zz['fields'][24]['display_field'] = sprintf('language_%s', $language_code);
	$zz['fields'][24]['exclude_from_search'] = true;
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
$zz['fields'][4]['hide_in_list_if_empty'] = true;
$zz['fields'][4]['class'] = 'hidden480';

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
	$zz['fields'][19]['type'] = 'text';
	$zz['fields'][19]['hide_in_list'] = true;
	$zz['fields'][19]['explanation'] = 'If it’s not a medium created by yourself, who created it, where did you find it?';
	$zz['fields'][19]['if'][2] = false;
	$zz['fields'][19]['separator'] = true;
	$zz['fields'][19]['sql'] = 'SELECT DISTINCT source, source FROM media ORDER BY source';
	$zz['fields'][19]['if']['add']['separator'] = false;
} else {
	$zz['fields'][5]['separator'] = true;
	$zz['fields'][5]['if']['add']['separator'] = false;
}

$zz['fields'][10]['field_name'] = 'filename';
$zz['fields'][10]['type'] = 'identifier';
$zz['fields'][10]['fields'] = ['main_medium_id[filename]', 'title'];
$zz['fields'][10]['class'] = 'hidden';
$zz['fields'][10]['hide_in_list'] = true;
$zz['fields'][10]['conf_identifier']['concat'] = ['/'];
$zz['fields'][10]['conf_identifier']['exists'] = '-';
$zz['fields'][10]['if'][3]['conf_identifier']['lowercase'] = false;
$zz['fields'][10]['if'][3]['conf_identifier']['replace'] = ['_' => '_'];

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
$zz['fields'][15]['character_set'] = 'latin1';
$zz['fields'][15]['hide_in_list_if_empty'] = true;
$zz['fields'][15]['class'] = 'block480a';

$zz['fields'][26]['title'] = 'Filesize';
$zz['fields'][26]['field_name'] = 'filesize';
$zz['fields'][26]['type'] = 'hidden';
$zz['fields'][26]['type_detail'] = 'number';
$zz['fields'][26]['unit'] = 'bytes';
$zz['fields'][26]['upload_field'] = 14;
$zz['fields'][26]['upload_value'] = 'size';
$zz['fields'][26]['hide_in_list'] = true;
$zz['fields'][26]['dont_show_missing'] = true;
$zz['fields'][26]['if']['add']['hide_in_form'] = true;
$zz['fields'][26]['if'][2]['hide_in_form'] = true;
$zz['fields'][26]['if'][3]['hide_in_form'] = true;
$zz['fields'][26]['hide_in_list_if_empty'] = true;
$zz['fields'][26]['class'] = 'block480';

$zz['fields'][34]['title'] = 'MD5';
$zz['fields'][34]['field_name'] = 'md5_hash';
$zz['fields'][34]['type'] = 'hidden';
$zz['fields'][34]['hide_in_list'] = true;
$zz['fields'][34]['upload_field'] = 14;
$zz['fields'][34]['upload_value'] = 'md5';
$zz['fields'][34]['dont_show_missing'] = true;
$zz['fields'][34]['exclude_from_search'] = true;
$zz['fields'][34]['if']['add']['hide_in_form'] = true;
$zz['fields'][34]['if'][2] = false;
$zz['fields'][34]['if'][3] = false;

$zz['fields'][37]['title_append'] = 'Size';
$zz['fields'][37]['title_tab'] = 'Size';
$zz['fields'][37]['title'] = 'Width';
$zz['fields'][37]['field_name'] = 'width_px';
$zz['fields'][37]['type'] = 'hidden';
$zz['fields'][37]['type_detail'] = 'number';
$zz['fields'][37]['unit'] = 'px';
$zz['fields'][37]['upload_field'] = 14;
// values for RAW or edited images are not correct in upload[width], exif[ImageWidth]
$zz['fields'][37]['upload_value'] = [
	'exiftool[SubIFD1][ImageWidth][val]',
	'exiftool[IFD0][ImageWidth][val]',
	'exiftool[File][ImageWidth][val]',
	'exif[COMPUTED][Width]',
	'exif[ImageWidth]',
	'upload[width]'
];
$zz['fields'][37]['if'][81]['upload_value'] = 'modified[width]';
$zz['fields'][37]['suffix'] = ' × ';
$zz['fields'][37]['list_suffix'] = ' × ';
$zz['fields'][37]['append_next'] = true;
$zz['fields'][37]['dont_show_missing'] = true;
$zz['fields'][37]['if']['add']['hide_in_form'] = true;
$zz['fields'][37]['if'][2]['hide_in_form'] = true;
$zz['fields'][37]['hide_in_list'] = true;
$zz['fields'][37]['hide_in_list_if_empty'] = true;
$zz['fields'][37]['list_append_next'] = true;
$zz['fields'][37]['if'][3]['type'] = 'number';

$zz['fields'][38]['title'] = 'Height';
$zz['fields'][38]['field_name'] = 'height_px';
$zz['fields'][38]['type'] = 'hidden';
$zz['fields'][38]['type_detail'] = 'number';
$zz['fields'][38]['unit'] = 'px';
$zz['fields'][38]['upload_field'] = 14;
$zz['fields'][38]['dont_show_missing'] = true;
// values for RAW or edited images are not correct in upload[height], exif[ImageLength]
$zz['fields'][38]['upload_value'] = [
	'exiftool[SubIFD1][ImageHeight][val]',
	'exiftool[IFD0][ImageHeight][val]',
	'exiftool[File][ImageHeight][val]',
	'exif[COMPUTED][Height]',
	'exif[ImageLength]',
	'upload[height]'
];
$zz['fields'][38]['if']['add']['hide_in_form'] = true;
$zz['fields'][38]['if'][2]['hide_in_form'] = true;
$zz['fields'][38]['hide_in_list'] = true;
$zz['fields'][38]['hide_in_list_if_empty'] = true;
$zz['fields'][38]['if'][3]['type'] = 'number';

$zz['fields'][35]['field_name'] = 'version';
$zz['fields'][35]['type'] = 'hidden';
$zz['fields'][35]['hide_in_list'] = true;
$zz['fields'][35]['upload_field'] = 14;
$zz['fields'][35]['upload_value'] = 'increment_on_change';
$zz['fields'][35]['dont_show_missing'] = true;
$zz['fields'][35]['if']['add']['hide_in_form'] = true;
$zz['fields'][35]['if'][2] = false;
$zz['fields'][35]['if'][3] = false;

$zz['fields'][40]['field_name'] = 'parameters';
$zz['fields'][40]['type'] = 'parameter';
$zz['fields'][40]['hide_in_list'] = true;
$zz['fields'][40]['hide_in_form'] = true;
$zz['fields'][40]['if'][3]['hide_in_form'] = false;

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
	wrap_filetype_id('folder'));
$zz['conditions'][2]['add']['sql'] = 'SELECT filetype_id
	FROM /*_PREFIX_*/filetypes o_mime
	WHERE filetype_id = ';
$zz['conditions'][2]['add']['key_field_name'] = 'filetype_id';

$zz['conditions'][3]['scope'] = 'record';
$zz['conditions'][3]['where'] = sprintf('o_mime.extension = "" AND o_mime.filetype_id != %d',
	wrap_filetype_id('folder'));
$zz['conditions'][3]['add']['sql'] = 'SELECT filetype_id
	FROM /*_PREFIX_*/filetypes o_mime
	WHERE filetype_id = ';
$zz['conditions'][3]['add']['key_field_name'] = 'filetype_id';

$zz['title'] = wrap_text('Media Pool');

$zz_conf['max_select'] = 100;
$zz_conf['limit'] = 42;

if (empty($brick['local_settings']['no_publish'])) {
	if (!isset($zz_conf['footer_text'])) $zz_conf['footer_text'] = '';
	$zz_conf['footer_text'] .= '<p><em>'.wrap_text('Coloured border: medium is published; gray border: medium is not published.').'</em></p>';
}
$zz_conf['list_display'] = 'ul';

$zz['add'][] = [
	'type' => wrap_text('File'),
	'field_name' => 'filetype_id',
	'value' => ''
];
$zz['add'][] = [
	'type' => wrap_text('Folder'),
	'field_name' => 'filetype_id',
	'value' => wrap_filetype_id('folder')
];
if (!empty($zz_setting['embed'])) {
	foreach (array_keys($zz_setting['embed']) as $embed) {
		$zz['add'][] = [
			'type' => $embed,
			'field_name' => 'filetype_id',
			'value' => wrap_filetype_id(strtolower($embed))
		];
	}
}

$zz['if'][3]['hooks']['before_insert'][] = 'mf_media_hook_embed';

$zz['page']['head'] = "\t".'<link rel="stylesheet" type="text/css" href="'.$zz_setting['layout_path'].'/media/zzform-media.css">'."\n";

$zz['set_redirect'][] = [
	'old' => $zz_setting['files_path'].'/%s*',
	'new' => $zz_setting['files_path'].'/%s*',
	'field_name' => 'filename'
];
