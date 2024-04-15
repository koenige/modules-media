<?php 

/**
 * media module
 * XHR request for cropping medium in mediuminfo
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * save cropped media info
 *
 * @param array $xmlHttpRequest
 *		double x
 *		double y
 *		double width
 *		double height
 * @return array
 */
function mod_media_xhr_crop($data) {
	wrap_access_quit('media_edit');
	// @todo check access rights to medium_id via media_access
	if (empty($data['medium_id'])) wrap_quit(404);

	$sql = 'SELECT medium_id, parameters FROM media WHERE medium_id = %d';
	$sql = sprintf($sql, $data['medium_id']);
	$medium = wrap_db_fetch($sql);
	if (!$medium) wrap_quit(404);
	parse_str($medium['parameters'], $medium['parameters']);

	$left = $data['x'] ?? 0;
	$top = $data['y'] ?? 0;
	$right = !empty($data['width']) ? $data['x'] + $data['width'] : 1;
	$bottom = !empty($data['height']) ? $data['y'] + $data['height'] : 1;

	$medium['parameters']['crop'] = sprintf('%s,%s,%s,%s', $left, $top, $right, $bottom);

	$line = [
		'medium_id' => $medium['medium_id'],
		'parameters' => http_build_query($medium['parameters']),
		'clipping' => 'custom'
	];
	$ops = zzform_update('media', $line, E_USER_ERROR, 'Unable to set crop coordinates');
	return 'success';
}
