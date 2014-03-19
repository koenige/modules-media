<?php 

/**
 * Zugzwang Project
 * Common functions
 * Allgemeine Funktionen
 *
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2012 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_media_switch_links($variants) {
	$text = '';
	foreach ($variants as $variant) {
		$link = $variant['link'] ? '<a href="'.$variant['link'].'">' : '';
		$link_end = $variant['link'] ? '</a>' : '';
		$text .= ' '.sprintf($link.'<img src="%s" alt="%s" title="%s" class="icon">'
			.$link_end, $variant['img'], $variant['alt'], $variant['title']);
	}
	return $text;
}
