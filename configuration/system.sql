/**
 * media module
 * SQL queries for core, page, auth and database IDs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


-- core_filetypes --
SELECT CONCAT(mime_content_type, '/', mime_subtype) FROM /*_PREFIX_*/filetypes
WHERE extension = _latin1'%s';

-- ids_filetypes --
SELECT filetype, filetype_id FROM /*_PREFIX_*/filetypes;

-- ids_folders --
SELECT filename, medium_id FROM /*_PREFIX_*/media WHERE filetype_id = /*_ID FILETYPES folder */;
