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