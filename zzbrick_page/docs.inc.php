<?php

/**
 * media module
 * Output linked documents
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * linked documents
 *
 * @param array $params
 */
function page_docs(&$params, $page) {
	if (empty($page['media']['links'])) return '';

	$page['media']['links'] = page_docs_remove_used($page['media']['links']);
	if (empty($page['media']['links'])) return '';
	
	$text = wrap_template('docs', $page['media']['links']);
	return $text;
}

/**
 * remove documents from list which are already used inline in text
 * both in original language and in translations
 *
 * @param array $links
 * @return array
 */
function page_docs_remove_used($links) {
	global $zz_page;
	global $zz_setting;
	$remove = [];
	$texts = [];
	$ignore_langs = [$zz_setting['lang']];
	
	// original language
	$texts[] = $zz_page['db']['content'];
	// translations
	if (!empty($zz_page['db']['wrap_source_content']['content'])) {
		$texts[] = $zz_page['db']['wrap_source_content']['content'];
		$ignore_langs[] = $zz_page['db']['wrap_source_language']['content'];
	}
	if (!empty($zz_setting['languages_allowed'])) {
		foreach ($zz_setting['languages_allowed'] as $lang) {
			if (in_array($lang, $ignore_langs)) continue;
			$translation = wrap_translate($zz_page['db'], 'webpages', 'page_id', true, $lang);
			$texts[] = $translation['content'];
		}
	}
	
	foreach ($texts as $text) {
		$blocks = explode('%%%', $text);
		foreach ($blocks as $index => $block) {
			if (!($index & 1)) continue;
			$block = trim($block);
			$block = explode(' ', $block);
			if (!in_array($block[0], ['doc', 'link'])) continue;
			$remove[] = $block[1];
		}
	}
	
	foreach ($links as $medium_id => $link) {
		if (!in_array($link['sequence'], $remove)) continue;
		unset($links[$medium_id]);
	}
	return $links;
}
