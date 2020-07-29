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

function mod_media_get_embed_youtube($video) {
	global $zz_setting;
	require_once $zz_setting['core'].'/syndication.inc.php';

	$url = sprintf($zz_setting['youtube_url'], $video);
	list($status, $headers, $data) = wrap_syndication_retrieve_via_http($url);

	// get opengraph metadata
	if ($status === 200) {
		preg_match_all('/<meta property=["\'](.+?)["\'] content=["\'](.+?)["\']>/', $data, $matches);
		foreach ($matches[1] as $index => $key) {
			if (!empty($meta[$key])) {
				if (!is_array($meta[$key])) $meta[$key] = [$meta[$key]];
				$meta[$key][] = $matches[2][$index];
			} else {
				$meta[$key] = $matches[2][$index];
			}
		}
		if (empty($meta)) $status = 404;
	}
	if (!in_array($status, [200, 429])) {
		wrap_error(sprintf('YouTube Video %s was not found. Status: %d', $video, $status));
		return '';
	}

	$meta['video'] = $video;
	return $meta;
}
