<?php

/* ロード
 * 
 * @param String $templatePath
 */
function loadText($templateItem)
{
	$text = false;
	if(preg_match('/\.md$/', $templateItem['realpath'])){
		$text = @file_get_contents($templateItem['realpath']);
	}
	if($text === false && $templateItem['type'] == 'dir') {
		// リスティングとしての.mdテキストを構築する
		$webroot = getWebRootDir();
		if ($templateItem['children']) {
			foreach ($templateItem['children'] as $child) {
				$webpath = $child['webpath'];
				$webpath = implode('/', array_map('rawurlencode', explode('/', $child['webpath'])));
				$text .= "- [{$child['name']}]({$webroot}{$webpath})\n";
			}
		}
	}
	if($text === false)$text = '';
	return $text;
}
