<?php

/*
 * REQUEST_URI からテンプレートファイル (*.md) を取得
 * 
 * REQUEST_URI … http://memo.clock-up.jp/hoge
 * 
 * @return String リアルパス  例: '/home/sites/memo.clock-up.jp/public_html/30_hoge.tpl', // ※数字が入ることもある
 */
function resolveTemplateItem()
{
	// webpath解決
	$webpath = urldecode($_SERVER['REQUEST_URI']);
	$webpath = preg_replace('/\?.*/', '', $webpath);
	$webpath = preg_replace('/\.html$/', '', $webpath);
	$webpath = preg_replace('/\.md$/', '', $webpath);
	$webpath = preg_replace('/^' . preg_quote(getWebRootDir(), '/') . '/', '', $webpath); // 頭の /memo を削る
	global $g_webpath2item;
	if(isset($g_webpath2item[$webpath])){
		$item = $g_webpath2item[$webpath];
		return $item;
	}
	
	// not found
	return $g_webpath2item['/404'];
}

	/*
	$template = APP_ROOT . $_SERVER['REQUEST_URI'];
	
	// html指定なら.mdに変更する
	if(preg_match('/\.html$/', $template)){ //「.html」で終わる場合
		$template = preg_replace('/\\.html$/', '.md', $template);
	}
	else if(preg_match('/\\/$/', $template)){ //「/」で終わる場合
		$template .= 'index.md';
	}
	else{
		$template .= '.md';
	}
	
	// URLデコード
	$template = urldecode($template);
	if(preg_match('/(\.\.|\|)/', $template)){ // 「..」や「|」が含まれていたらテンプレートをクリアする（セキュリティ対応）
		$template = '';
	}
	
	// ファイルの存在を確認
	if(!file_exists($template)){
		// index.html のときのみ例外
		if(preg_match('/\/index\.md$/', $template)){
			// フォルダと同名の.mdがあればそちらを出力
			$folder_item = preg_replace('/\/index\.md$/', '.md', $template);
			if(file_exists($folder_item)){
				$template = $folder_item;
			}
		}
		// 通常は Not Found にする
		else{
			header("HTTP/1.1 404 Not Found");
			//print "<html><body>page not found...</body></html>";
			//exit;
			$template = '404.md';
		}
	}
	*/
