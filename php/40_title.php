<?php

function getTitleItems()
{
	$webpath = urldecode($_SERVER['REQUEST_URI']);
	$webpath = preg_replace('/^' . preg_quote(getWebRootDir(), '/') . '/', '', $webpath); // 頭の /memo を削る
	$webpath = preg_replace('/\?.*/', '', $webpath);
	$webpath = preg_replace('/\.html$/', '', $webpath);
	$webpath = preg_replace('/\/index$/', '', $webpath);
	$names = explode('/', $webpath); // [Top, Ubuntu, samba]
	// items構築
	$items = array();
	$webpath = '';
	foreach($names as $index => $name){
		if($index == 0){
			$webpath = '/';
		}
		else{
			if($webpath != '/')$webpath .= '/';
			$webpath .= $name;
		}
		global $g_webpath2item;
		if(isset($g_webpath2item[$webpath])){
			$item = $g_webpath2item[$webpath];
		}
		else{
			$item = array();
			$item['webpath'] = $webpath;
			$item['name'] = $name;
		}
		$items[] = $item;
	}
	return $items;
}

// デフォルトタイトル
function getH1Title($items)
{
	$webroot = getWebRootDir(); // /memo
	$ret = "";
	$ret .= "<ol class='breadcrumb' style='padding: 0px;'>\n";
	foreach($items as $index => $item){
		if($index == count($items) - 1){
			$ret .= "<li class='active'><span><span>{$item['name']}</span></li>\n";
		}
		else if($index == 0){
			$ret .= "<li><span><a href='{$webroot}'><span>{$item['name']}</span></a></li>\n";
		}
		else{
			$ret .= "<li><span><a href='{$webroot}{$item['webpath']}'><span>{$item['name']}</span></a></li>\n";
		}
	}
	$ret .= "</ol>\n";
	return $ret;
}
function getHeadTitle($items)
{
	$ret = '';
	foreach($items as $index => $item){
		if($index == 0){
		}
		else{
			if($ret != '')$ret .= '/';
			$ret .= $item['name'];
		}
	}
	if($ret != '')$ret .= ' - ';
	$ret .= getSiteName();
	return $ret;
}
