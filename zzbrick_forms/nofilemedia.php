<?php 

/**
 * Zugzwang Project
 * upload no file media
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz = zzform_include_table('media');

// no image required
$zz['fields'][14]['image'][0]['required'] = false;

// filename
$zz['fields'][10]['type'] = 'text';

// no thumb filetype
$zz['fields'][16] = [];
