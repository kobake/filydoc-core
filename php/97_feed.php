<?php

// http://feedcreator.org/
// http://tutty.info/431

// タイムゾーン
define("TIME_ZONE","+09:00");
/*
setDefaultTimezone('Asia/Tokyo');
function setDefaultTimezone($timezone) {
	if (!ini_get('date.timezone')) {
		date_default_timezone_set($timezone);
	}
}
*/

// ライブラリ
require_once(APP_ROOT . '/php/libs/feedcreator.class.php');

// items
function dirs2items($dirs)
{
	$items = array();
	_dirs2items($dirs, $items);
	return $items;
}
function _dirs2items($items, &$out_items)
{
	foreach($items as $item){
		if($item['type'] == 'dir'){
			if(isset($item['children']) && count($item['children']) > 0){
				_dirs2items($item['children'], $out_items);
			}
		}
		else{
			$out_items[] = $item;
		}
	}
}

// 処理開始
function saveFeed(){
	// get items
	$dirs = get_dirs();
	$items = dirs2items($dirs);

	// updated降順でソート
	usort($items, function($a, $b){
		return $b['updated'] - $a['updated'];
	});
	$items = array_slice($items, 0, 50); // 最新50件

	// var_dump($items);
	// exit(0);

	// feed channel
	$rss = new UniversalFeedCreator();
	// $rss->useCached();
	$rss->title          = FeedSettings::SITE_TITLE;
	$rss->description    = FeedSettings::SITE_DESCRIPTION;
	$rss->link           = FeedSettings::SITE_URL;
	$rss->syndicationURL = FeedSettings::SITE_URL . '/feed.xml';
	$rss->date           = time();

	// feed items
	foreach($items as $item){
		// channel items/entries
		$fitem = new FeedItem();
		$fitem->title       = $item['webpath'];
		$fitem->link        = FeedSettings::SITE_URL . $item['webpath'];
		$fitem->description = $item['name'];
		// $fitem->source      = "http://mydomain.net";
		$fitem->author      = FeedSettings::AUTHOR_MAIL;
		$fitem->date        = $item['updated'];

		// optional enclosure support
		/*
		$fitem->enclosure         = new EnclosureItem();
		$fitem->enclosure->url    = 'http://mydomain.net/news/picture.jpg';
		$fitem->enclosure->length = "65905";
		$fitem->enclosure->type   = 'image/jpeg';
		*/
		$rss->addItem($fitem);
	}

	//Valid parameters are RSS0.91, RSS1.0, RSS2.0, PIE0.1 (deprecated),
	// MBOX, OPML, ATOM, ATOM1.0, ATOM0.3, HTML, JS

	// $rss->outputFeed("ATOM1.0");
	$rss->saveFeed("ATOM1.0", "./feed.xml", false);
}
