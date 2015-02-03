<?php

/* ロード
 * 
 * @param Array $templateItem
 * @return String/Boolean ロードした生テキストまたはfalse
 */
function loadText($templateItem)
{
	$text = false;
	// .mdはそのまま内容を返す
	if(preg_match('/\.md$/', $templateItem['realpath'])){
		$text = @file_get_contents($templateItem['realpath']);
	}
	// .java, .php はそのまま内容を返す
	elseif(preg_match('/\.(java|php)$/', $templateItem['realpath'])){
		$text = @file_get_contents($templateItem['realpath']);
	}
	// .txtはそのまま内容を返す
	elseif(preg_match('/\.txt/', $templateItem['realpath'])){
		$text = @file_get_contents($templateItem['realpath']);
	}

	// ディレクトリはその中のアイテム一覧をmarkdown化したものを返す
	if($text === false && $templateItem['type'] == 'dir') {
		// リスティングとしての.mdテキストを構築する
		$webroot = getWebRootDir();
		if ($templateItem['children']) {
			$text = "<!-- index items -->\n\n";
			foreach ($templateItem['children'] as $child) {
				$webpath = $child['webpath'];
				$webpath = implode('/', array_map('rawurlencode', explode('/', $child['webpath'])));
				$text .= "- [{$child['name']}]({$webroot}{$webpath})\n";
			}
		}
	}
	if($text === false)$text = '';

	// 加工
	$text = str_replace('＜', '&lt;', $text);

	return $text;
}

/* セーブ
 *
 * @param Array  $templateItem
 * @param Int/Boolean The number of bytes that were written to the file, or FALSE on failure.
 */
function saveText($templateItem, $text)
{
	// これだとエラー原因が分からないので、生ファイルアクセスを試みる
	/*
	retrun @file_put_contents($templateItem['realpath'], $text);
	$ret = @file_put_contents($templateItem['realpath'], $text);
	if($ret === false){
		global $php_errormsg;
		print("Error: $php_errormsg\n");
	}
	return $ret;
	*/

	ini_set('track_errors', '1');
	$ret = @file_put_contents($templateItem['realpath'], $text);
	ini_set('track_errors', '0');
	if($ret === false){
		throw new Exception("$php_errormsg");
	}
	return $ret;


	/*
	ini_set('track_errors', '1');
	$fp = @fopen($templateItem['realpath'], 'w');
	ini_set('track_errors', '0');
	if(!$fp){
		throw new Exception("fopen failure: $php_errormsg");
	}
	fwrite($fp, $text);
	fclose($fp);
	return true;
	*/
}
