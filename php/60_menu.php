<?php

// メニュー
function get_items_html($dirs)
{
	$ret = _get_items_html($dirs, '', 0);
	return $ret;
}

function _get_items_html($items, $indent, $level)
{
	global $g_items;
	$ret = "";
	$i = 0;
	$webroot = getWebRootDir();
	foreach($items as $item){
		if($item['name'] == 'index')continue;
		$ret .= $indent . "<li>\n";
		$ret .= $indent . "  <a href='{$webroot}{$item['webpath']}'><span>{$item['name']}</span></a>\n";
		if($item['type'] == 'dir'){
			if(isset($item['children']) && count($item['children']) > 0){
				$ret .= _get_items_html($item['children'], $indent . '    ', $level + 1);
			}
		}
		$ret .= $indent . "</li>\n";
		$i++;
	}
	if($ret !== ""){
		$ret = "{$indent}<ul>\n" . $ret . "{$indent}</ul>\n";
	}
	return $ret;
}
