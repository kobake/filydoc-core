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
	if($text === false && $templateItem['type'] === 'dir') {
		// リスティングとしての.mdテキストを構築する
		$webroot = getWebRootDir();
		if ($templateItem['children']) {
			$text = "<!-- index items -->\n\n";
			foreach ($templateItem['children'] as $child) {
				$webpath = $child['webpath'];
				$webpath = implode('/', array_map('rawurlencode', explode('/', $child['webpath'])));
				$text .= "- [{$child['name']}]({$webroot}{$webpath})\n";
			}
			// 最下部にアイテム追加ボタンを付ける
			$text .= <<< EOS
EOS;
		}
	}
	if($text === false)$text = '';

	// 加工
	$text = str_replace('＜', '&lt;', $text);

	return $text;
}

// 拡張リネーム
// ※ディレクトリが存在しない場合にはディレクトリも自動生成
function rename_ex($oldname, $newname){
	mkdir(dirname($newname), 0777, TRUE);
	rename($oldname, $newname);
}

/* セーブ
 *
 * @param Array  $templateItem
 * @param String $text
 * @param String $editType
 * @param String $originalPath
 * @param String $editPath
 *
 * @return Int/Boolean The number of bytes that were written to the file, or FALSE on failure.
 */
function saveText($templateItem, $text, $editType, $originalPath, $editPath)
{
	// ファイル内容保存
	if($editType === 'new'){
		$savePath = DATA_ROOT . '/' . $editPath;
	}
	else{
		$savePath = $templateItem['realpath'];
	}
	ini_set('track_errors', '1');
	$ret = @file_put_contents($savePath, $text);
	ini_set('track_errors', '0');
	if($ret === false){
		throw new Exception("$php_errormsg");
	}

	// Path変更
	if($originalPath !== $editPath && $editType !== 'new'){
		// 名前変更
		$originalFullPath = DATA_ROOT . '/' . $originalPath;
		$editFullPath = DATA_ROOT . '/' . $editPath;
		rename_ex($originalFullPath, $editFullPath);
	}

	// Path適用
	return $ret;
}
