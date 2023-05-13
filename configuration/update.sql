/**
 * media module
 * SQL updates
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2017, 2020-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/* 2017-07-21-1 */	ALTER TABLE `filetypes` CHANGE `filetype` `filetype` varchar(7) COLLATE 'latin1_general_ci' NOT NULL AFTER `filetype_id`, CHANGE `mime_content_type` `mime_content_type` varchar(31) COLLATE 'latin1_general_cs' NOT NULL AFTER `filetype`, CHANGE `mime_subtype` `mime_subtype` varchar(127) COLLATE 'latin1_general_cs' NOT NULL AFTER `mime_content_type`, CHANGE `filetype_description` `filetype_description` varchar(63) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `mime_subtype`, COLLATE 'utf8mb4_unicode_ci';
/* 2017-07-21-2 */	ALTER TABLE `media` CHANGE `title` `title` varchar(127) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `main_medium_id`, CHANGE `description` `description` text COLLATE 'utf8mb4_unicode_ci' NULL AFTER `title`, CHANGE `source` `source` varchar(255) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `time`, ADD `clipping` enum('center','top','right','bottom','left') COLLATE 'latin1_general_ci' NOT NULL DEFAULT 'center' AFTER `published`, CHANGE `filename` `filename` varchar(255) COLLATE 'latin1_general_cs' NOT NULL AFTER `sequence`, ADD `width_px` mediumint unsigned NULL AFTER `version`, ADD `height_px` mediumint unsigned NULL AFTER `width_px`, COLLATE 'utf8mb4_unicode_ci';
/* 2020-05-19-1 */	ALTER TABLE `media` ADD `parameters` varchar(255) NULL AFTER `height_px`;
/* 2020-05-19-2 */	INSERT INTO filetypes (`filetype`, `filetype_description`, `mime_content_type`, `mime_subtype`, `extension`) VALUES ('youtube', 'YouTube Video', 'application', 'octet-stream', '');
/* 2020-05-26-1 */	ALTER TABLE `media` CHANGE `parameters` `parameters` varchar(750) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `height_px`;
/* 2020-05-27-1 */	ALTER TABLE `media` CHANGE `parameters` `parameters` text COLLATE 'utf8mb4_unicode_ci' NULL AFTER `height_px`;
/* 2020-12-09-1 */	CREATE TABLE `media_access` (`medium_access_id` int unsigned NOT NULL AUTO_INCREMENT, `medium_id` int unsigned DEFAULT NULL, `usergroup_id` int unsigned NOT NULL, `access_category_id` int unsigned NOT NULL, `last_update` timestamp NOT NULL, PRIMARY KEY (`medium_access_id`), UNIQUE KEY `medium_id_usergroup_id` (`medium_id`,`usergroup_id`), KEY `usergroup_id` (`usergroup_id`), KEY `access_category_id` (`access_category_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/* 2020-12-09-2 */	INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'media', 'medium_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'medium_id', 'delete');
/* 2020-12-09-3 */	INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'usergroups', 'usergroup_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'usergroup_id', 'no-delete');
/* 2020-12-09-4 */	INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'categories', 'category_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'access_category_id', 'no-delete');
/* 2021-04-24-1 */	ALTER TABLE `media` ADD `alternative_text` varchar(500) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `description`;
/* 2021-10-31-1 */	ALTER TABLE `media` CHANGE `clipping` `clipping` enum('center','top','bottom','right','left','custom') COLLATE 'latin1_general_ci' NOT NULL DEFAULT 'center' AFTER `published`;
/* 2022-09-29-1 */	ALTER TABLE `media` ADD INDEX `filename` (`filename`);
/* 2023-05-13-1 */	INSERT INTO categories (`category`, `description`, `main_category_id`, `path`, `parameters`, `sequence`, `last_update`) VALUES ('Media', NULL, (SELECT category_id FROM categories c WHERE path = 'jobs'), 'jobs/media', '&alias=jobs/media&max_requests=2', NULL, NOW());
