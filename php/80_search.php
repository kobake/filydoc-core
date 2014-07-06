<?php

// 検索
// 実際にはgrepで結果を抜き出す (これはレンタルサーバでも使える？)
// grep -R -i Chrome /home/sites/clock-up.jp/memo.clock-up.jp/public_html/data/
function searchAndGenerateMarkdownText($keyword)
{
	if(mb_strlen($keyword) < 2){
		return "- 2文字以上のキーワードを入力してください";
	}
	// 起動コマンドの構築
	$dir_path = DATA_ROOT;
	$cmd = "grep "
		. " " . "-R -i -n" // recursive, ignore case, line number
		. " " . escapeshellarg($keyword)
		. " " . escapeshellarg($dir_path);
	
	// grep起動
	//exec($cmd, $output, $ret);
	$handle = popen($cmd, 'r');
	$webpathItems = array();
	while(!feof($handle)){
		// 1行
		$line = fgets($handle);
		$line = rtrim($line);
		// 行解釈
		if(preg_match('/([^\:]+)\:([0-9]+)\:(.*)/', $line, $m)){
			$realpath = $m[1];
			$lineNumber = $m[2];
			$content = $m[3];
			// webpath, namepath
			{
				// 共通加工 -> $tmp
				$tmp = $realpath;
				$tmp = preg_replace('/' . preg_quote($dir_path, '/') . '/', '', $tmp); // パス省略
				$tmp = preg_replace('/\.md/', '', $tmp, -1, $count); // 拡張子除去
				if($count <= 0)continue; // .md でないファイルは除外
				// $tmp -> $webpath
				$webpath = $tmp;
				$webpath = preg_replace('/\/[0-9]+\_/', '/', $webpath); // 先頭数字を除去
				$webpath = preg_replace('/\/[A-Za-z0-9\-]+\_/', '/', $webpath); // 先頭アルファベットキーを除去
				// $tmp -> $namepath
				$namepath = $tmp;
				$namepath = preg_replace('/\/[0-9]+\_/', '/', $namepath); // 先頭数字を除去
				$namepath = preg_replace('/\/([A-Za-z0-9\-]+)\_([^\/]+)/', '/\1', $namepath); // 先頭アルファベットキーを「採用」
			}
			// コンテンツ
			{
				$content = preg_replace('/[\\\`\*\_\{\}\[\]\(\)\#\+\-\.\!]/', '\\\\\0', $content); // markdownエスケープ
				$content = str_replace('<', '&lt;', $content); // htmlエスケープ
				$content = str_replace('>', '&gt;', $content); // htmlエスケープ
				$content = preg_replace('/' . preg_quote($keyword, '/') . '/i', '**\0**', $content); // 一致箇所の強調表示
			}
			// 同じパスからのコンテンツはまとめる
			if(!isset($webpathItems[$webpath]))$webpathItems[$webpath] = array();
			if(!isset($webpathItems[$webpath]['contents']))$webpathItems[$webpath]['items'] = array();
			$webpathItems[$webpath]['namepath'] = $namepath;
			$webpathItems[$webpath]['contents'][] = array('number' => $lineNumber, 'content' => $content);
		}
		else{
			// 何もしない
		}
	}
	pclose($handle);
	// 結果をまとめる
	$ret = '';
	$webroot = getWebRootDir();
	foreach($webpathItems as $webpath => $item){
		// markdown output
		$ret .= "- [{$webpath}]({$webroot}{$item['namepath']})\n";
		//$ret .= "- {$webpath}\n";
		foreach($item['contents'] as $content){
			$ret .= "    - [{$content['number']}] {$content['content']}\n";
		}
	}
	// 結果
	return $ret;
}
