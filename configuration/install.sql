/**
 * media module
 * SQL for installation of media module
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


-- filetypes --
CREATE TABLE `filetypes` (
  `filetype_id` int unsigned NOT NULL AUTO_INCREMENT,
  `filetype` varchar(10) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `mime_content_type` varchar(31) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `mime_subtype` varchar(127) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `filetype_description` varchar(63) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension` varchar(7) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`filetype_id`),
  UNIQUE KEY `filetype` (`filetype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `filetypes` (`filetype_id`, `filetype`, `mime_content_type`, `mime_subtype`, `filetype_description`, `extension`) VALUES
(1,	'jpeg',	'image',	'jpeg',	'JPEG Image',	'jpeg'),
(2,	'gif',	'image',	'gif',	'GIF Image',	'gif'),
(3,	'pdf',	'application',	'pdf',	'Portable Document Format',	'pdf'),
(4,	'txt',	'text',	'plain',	'Text File',	'txt'),
(5,	'html',	'text',	'html',	'Hypertext Markup Language',	'html'),
(6,	'tiff',	'image',	'tiff',	'TIFF Image',	'tif'),
(7,	'png',	'image',	'png',	'Portable Network Graphic',	'png'),
(8,	'rtf',	'text',	'rtf',	'Rich Text Format',	'rtf'),
(9,	'xls',	'application',	'vnd.ms-excel',	'MS Excel Spreadsheet',	'xls'),
(10,	'xlsx',	'application',	'vnd.openxmlformats-officedocument.spreadsheetml.sheet',	'MS Excel Open XML Spreadsheet',	'xls'),
(11,	'zip',	'application',	'zip',	'ZIP Archive',	'zip'),
(12,	'doc',	'application',	'msword',	'MS Word Document',	'doc'),
(13,	'docx',	'application',	'vnd.openxmlformats-officedocument.wordprocessingml.document',	'MS Word Open XML Document',	'docx'),
(14,	'folder',	'example',	'x-folder',	NULL,	''),
(15,	'css',	'text',	'css',	'Cascading Stylesheets',	'css'),
(16,	'js',	'application',	'javascript',	'JavaScript',	'js'),
(17,	'm4v',	'video',	'mp4',	'MPEG v4 Video',	'mp4'),
(18,	'mp3',	'audio',	'mp3',	'MPEG Audio Stream, Layer III',	'mp3'),
(19,	'youtube',	'application',	'octet-stream',	'YouTube Video',	''),
(20,	'svg',	'image',	'svg+xml',	'Scalable Vector Graphics',	'svg');


-- media --
CREATE TABLE `media` (
  `medium_id` int unsigned NOT NULL AUTO_INCREMENT,
  `main_medium_id` int unsigned DEFAULT NULL,
  `title` varchar(127) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alternative_text` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language_id` int unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published` enum('yes','no') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'yes',
  `clipping` enum('center','top','right','bottom','left','custom') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'center',
  `sequence` smallint unsigned DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `filetype_id` int unsigned NOT NULL,
  `thumb_filetype_id` int unsigned DEFAULT NULL,
  `filesize` int unsigned DEFAULT NULL,
  `md5_hash` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `version` tinyint unsigned DEFAULT NULL,
  `width_px` mediumint unsigned DEFAULT NULL,
  `height_px` mediumint unsigned DEFAULT NULL,
  `parameters` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`medium_id`),
  KEY `filetype_id` (`filetype_id`),
  KEY `thumb_filetype_id` (`thumb_filetype_id`),
  KEY `main_medium_id` (`main_medium_id`),
  KEY `filename` (`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'media', 'medium_id', (SELECT DATABASE()), 'media', 'medium_id', 'main_medium_id', 'no-delete');
INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'languages', 'language_id', (SELECT DATABASE()), 'media', 'medium_id', 'language_id', 'no-delete');
INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'filetypes', 'filetype_id', (SELECT DATABASE()), 'media', 'medium_id', 'filetype_id', 'no-delete');
INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'filetypes', 'filetype_id', (SELECT DATABASE()), 'media', 'medium_id', 'thumb_filetype_id', 'no-delete');


-- media_access --
CREATE TABLE `media_access` (
  `medium_access_id` int unsigned NOT NULL AUTO_INCREMENT,
  `medium_id` int unsigned DEFAULT NULL,
  `usergroup_id` int unsigned NOT NULL,
  `access_category_id` int unsigned NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`medium_access_id`),
  UNIQUE KEY `medium_id_usergroup_id` (`medium_id`,`usergroup_id`),
  KEY `usergroup_id` (`usergroup_id`),
  KEY `access_category_id` (`access_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'media', 'medium_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'medium_id', 'delete');
INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'usergroups', 'usergroup_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'usergroup_id', 'no-delete');
INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'categories', 'category_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'access_category_id', 'no-delete');


-- media_categories --
CREATE TABLE `media_categories` (
  `medium_category_id` int unsigned NOT NULL AUTO_INCREMENT,
  `medium_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  `property` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_category_id` int unsigned NOT NULL,
  `sequence` tinyint DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`medium_category_id`),
  UNIQUE KEY `category_id` (`category_id`,`medium_id`),
  KEY `medium_id` (`medium_id`),
  KEY `type_category_id` (`type_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'media', 'medium_id', (SELECT DATABASE()), 'media_categories', 'medium_category_id', 'medium_id', 'delete');
INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'categories', 'category_id', (SELECT DATABASE()), 'media_categories', 'medium_category_id', 'category_id', 'no-delete');
INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'categories', 'category_id', (SELECT DATABASE()), 'media_categories', 'medium_category_id', 'type_category_id', 'no-delete');

INSERT INTO categories (`category`, `description`, `main_category_id`, `path`, `parameters`, `sequence`, `last_update`) VALUES ('Tags', NULL, NULL, 'tags', '&alias=tags', NULL, NOW());


-- jobmanager --
INSERT INTO categories (`category`, `description`, `main_category_id`, `path`, `parameters`, `sequence`, `last_update`) VALUES ('Media', NULL, (SELECT category_id FROM categories c WHERE path = 'jobs'), 'jobs/media', '&alias=jobs/media&max_requests=2', NULL, NOW());
