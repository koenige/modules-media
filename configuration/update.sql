/**
 * Zugzwang Project
 * SQL for installation of media module
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/* 2020-05-19-1 */	ALTER TABLE `media` ADD `parameters` varchar(255) NULL AFTER `height_px`;
/* 2020-05-19-2 */	INSERT INTO filetypes (`filetype`, `filetype_description`, `mime_content_type`, `mime_subtype`, `extension`) VALUES ('youtube', 'YouTube Video', 'application', 'octet-stream', '');
/* 2020-05-26-1 */	ALTER TABLE `media` CHANGE `parameters` `parameters` varchar(750) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `height_px`;
/* 2020-05-27-1 */	ALTER TABLE `media` CHANGE `parameters` `parameters` text COLLATE 'utf8mb4_unicode_ci' NULL AFTER `height_px`;
/* 2020-12-09-1 */	CREATE TABLE `media_access` (`medium_access_id` int unsigned NOT NULL AUTO_INCREMENT, `medium_id` int unsigned DEFAULT NULL, `usergroup_id` int unsigned NOT NULL, `access_category_id` int unsigned NOT NULL, `last_update` timestamp NOT NULL, PRIMARY KEY (`medium_access_id`), UNIQUE KEY `medium_id_usergroup_id` (`medium_id`,`usergroup_id`), KEY `usergroup_id` (`usergroup_id`), KEY `access_category_id` (`access_category_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/* 2020-12-09-2 */	INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'media', 'medium_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'medium_id', 'delete');
/* 2020-12-09-3 */	INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'usergroups', 'usergroup_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'usergroup_id', 'no-delete');
/* 2020-12-09-4 */	INSERT INTO _relations (`master_db`, `master_table`, `master_field`, `detail_db`, `detail_table`, `detail_id_field`, `detail_field`, `delete`) VALUES ((SELECT DATABASE()), 'categories', 'category_id', (SELECT DATABASE()), 'media_access', 'medium_access_id', 'access_category_id', 'no-delete');
