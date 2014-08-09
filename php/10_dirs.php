<?php

define('CACHE_VERSION', 'cache1.08');
// echo "ほげ"
// @param String $real_path
// @param String $web_path
// @return array
// 例
/*
array (size=75)
  0 => 
    array (size=3)
      'name' => string '404' (length=3)
      'webpath' => string '/404' (length=4)
      'type' => string 'file' (length=4)
  1 => 
    array (size=4)
      'name' => string 'Chrome' (length=6)
      'webpath' => string '/Chrome' (length=7)
      'type' => string 'dir' (length=3)
      'children' => 
        array (size=6)
          0 => 
            array (size=3)
              'name' => string 'index' (length=5)
              'webpath' => string '/Chrome/index' (length=13)
              'type' => string 'file' (length=4)
          1 => 
            array (size=3)
              'name' => string 'ビルド' (length=9)
              'webpath' => string '/Chrome/ビルド' (length=17)
              'type' => string 'file' (length=4)
  2 => 
    array (size=3)
      'name' => string 'C＋＋' (length=7)
      'webpath' => string '/C＋＋' (length=8)
      'type' => string 'file' (length=4)
  5 => 
    array (size=3)
      'name' => string 'Ubuntu' (length=6)
      'webpath' => string '/Ubuntu' (length=7)
      'type' => string 'file' (length=4)
  6 => 
    array (size=4)
      'name' => string 'Ubuntu' (length=6)
      'webpath' => string '/Ubuntu' (length=7)
      'type' => string 'dir' (length=3)
      'children' => 
        array (size=13)
          0 => 
            array (size=3)
              'name' => string 'samba' (length=5)
              'webpath' => string '/Ubuntu/samba' (length=13)
              'type' => string 'file' (length=4)
 */
global $g_realpath2item;
global $g_webpath2item;
$g_realpath2item = array();
$g_webpath2item = array();

function loadCache($name)
{
	$data = @file_get_contents(TMP_ROOT . '/' . $name . '.serialized');
	if($data === false)return false;
	return unserialize($data);
}

function saveCache($name, $variable)
{
	$data = serialize($variable);
	@chmod(TMP_ROOT, 0777);
	@file_put_contents(TMP_ROOT. '/' . $name . '.serialized', $data);
}

// 指定のタイムスタンプより大きいタイムスタンプのファイルを探す
function findNewerFile($dirPath, $timestamp){
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
	foreach ($iterator as $item) {
		//if ($item->isFile()) {
		$t = $item->getMTime();
		if($t > $timestamp)return $item;
	}
	return false;
}

// ディレクトリの更新日時（中のファイルのうち最も最新の更新日時）を取得
function GetRecentlyModifiedTime($dirPath)
{
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
	$mtime = -1;
	foreach ($iterator as $fileinfo) {
		//if ($fileinfo->isFile()) {
			$t = $fileinfo->getMTime();
			// var_dump($fileinfo->getFilename());
			if ($t > $mtime) {
				// $file = $fileinfo->getFilename();
				$mtime = $t;
			}
		//}
	}
	return $mtime;
}

/*
 * itemのキー
 * ・key      … ソートに使われる
 * ・type
 * ・name
 * ・webpath
 * ・realpath
 * ・keywords … フィルタに使われる
 */
function get_dirs()
{
	global $g_sitename;
	global $g_copyright;
	global $g_ga;
	global $g_realpath2item;
	global $g_webpath2item;
	
	// タイムスタンプ比較
	$t_cache = filemtime(TMP_ROOT . '/all.serialized'); // キャッシュデータ
	$force_restruct = false;
	if($t_cache === false){ // キャッシュが無い場合、強制再構築
		$force_restruct = true;
	}
	else{
		// キャッシュがオリジナルより古い（小さい）場合、強制再構築。※キャッシュは少なくともオリジナルより3秒以上新しい必要がある。
		// $start = microtime(true);
		$found = findNewerFile(DATA_ROOT, $t_cache - 4); // (キャッシュ更新日時-4秒)より新しい(タイムスタンプが大きい)ファイルを探す
		// $end = microtime(true);
		// $t = $end - $start;
		if($found){
			$force_restruct = true;
		}
		// var_dump($force_restruct);exit(0);
	}
	
	// キャッシュロード
	if($force_restruct){
		// 強制再構築が必要な場合はloadCacheを行わない
		$all = false;
	}
	else{
		AppLog("loadCache");
		$all = loadCache('all');
		// バージョンが違ったりしたら無効化する
		if(!isset($all['version']) || $all['version'] !== CACHE_VERSION){
			$all = false;
		}
		else if(!isset($all['dataroot']) || $all['dataroot'] !== DATA_ROOT){
			$all = false;
		}
	}
	
	// キャッシュが無効ならキャッシュ再構築
	if($all === false){
		// $dirs構築
		$dirs = _get_dirs(DATA_ROOT, '');
		// 重複削除
		_cut_duplicated_items(null, $dirs);
		// ハッシュ構築 -> $g_realpath2item, $g_webpath2item
		$g_realpath2item = array();
		$g_webpath2item = array();
		_make_hash($dirs);
		// ハッシュに / も追加する
		$top = array();
		$top['key'] = 'Top';
		$top['type'] = 'file';
		$top['name'] = 'Top';
		$top['webpath'] = '/';
		$top['realpath'] = DATA_ROOT . '/index.md';
		$top['children'] = $dirs;
		$g_realpath2item[$top['realpath']] = $top;
		$g_webpath2item[$top['webpath']] = $top;
		$g_webpath2item['/404'] = array(
			'key' => '404',
			'type' => 'file',
			'name' => '404',
			'webpath' => './404',
			'realpath' => APP_ROOT . '/templates/404.md'
		);
		// keywords取得
		loadKeywords($top, '');
		// キャッシュ保存
		$all = array(
			'version' => CACHE_VERSION,
			'dataroot' => DATA_ROOT,
			'sitename' => getSiteName(),
			'copyright' => getCopyright(),
			'ga' => $g_ga,
			'top' => $top,
			'realpath2item' => $g_realpath2item,
			'webpath2item' => $g_webpath2item,
		);
		AppLog("saveCache");
		saveCache('all', $all);
	}
	
	// 結果
	$top = $all['top'];
	$g_sitename = $all['sitename'];
	$g_copyright = $all['copyright'];
	$g_ga = $all['ga'];
	$g_realpath2item = $all['realpath2item'];
	$g_webpath2item = $all['webpath2item'];
	return $top['children'];
}
function loadKeywords(&$item, $additionalKeywordsString)
{
	// 自分のコンテンツをロード (metasのため)
	// $item['body'] = explode("\n", file_get_contents($item['realpath']))[0];
	$body = file_get_contents($item['realpath']);
	$metas = readMetas($body);
	if(!isset($metas['keywords']))$metas['keywords'] = '';
	// 自分のキーワード
	$keywords = array(
		// $additionalKeywordsString, // 親のキーワードも引き継ぐ
		$item['key'], // 「00_ubuntu_うぶんつ」みたいな。
		$metas['keywords']
	);
	$item['keywords'] = mb_strtolower(implode(', ', $keywords));
	// サイト名もついでに取得
	if(isset($metas['sitename'])){
		global $g_sitename;
		$g_sitename = $metas['sitename'];
	}
	// Copyrightもついでに取得
	if(isset($metas['copyright'])){
		global $g_copyright;
		$g_copyright = $metas['copyright'];
	}
	if(isset($metas['ga'])){
		global $g_ga;
		$g_ga = $metas['ga'];
	}
	// 子がいれば子のキーワードをロード
	if(isset($item['children'])){
		foreach($item['children'] as &$child){
			loadKeywords($child, $item['keywords']);
		}
	}
}

// ハッシュ構築
function _make_hash($dirs)
{
	global $g_realpath2item;
	global $g_webpath2item;
	foreach($dirs as $index => $item){		
		// 普通に保存
		$g_realpath2item[$item['realpath']] = $item;
		$g_webpath2item[$item['webpath']] = $item; // ここはフォルダと.mdで重複する可能性がある。.md優先。
		
		// ディレクトリの場合は {webpath}/index を別名として記録する
		if($item['type'] == 'dir'){
			$registered_item = $item;
			
			// エイリアス
			$webpath_alias = $item['webpath'] . '/index';
			$g_webpath2item[$webpath_alias] = $registered_item;
		}

		// ハッシュに保存した後はrealpathを除去する (json_encodeによりrealpathを公開してしまうことを防ぐ)
		// ※ちなみにここは参照ではなく実体なので、保存済みハッシュにはunsetは影響しない
		// unset($item['realpath']);
		
		// 子があればさらに子について
		if(isset($item['children'])){
			_make_hash($item['children']);
		}
	}
}

// 重複する項目を削除する (同じ名前の.mdとフォルダがある場合等) (.md側を削除)
function _cut_duplicated_items($parent, &$dirs)
{
	// リストスキャン。削除するものにはdeleteフラグを立てる
	foreach($dirs as $index => &$item){
		// 直前のアイテム
		$last = array();
		$last['name'] = '';
		if($index > 0){
			$last = $dirs[$index - 1];
		}
		
		// 子要素としてindexがあればそれを削除し、realpathのみ受け継ぐ
		if(isset($item['children'])){
			foreach($item['children'] as &$child){
				if($child['name'] == 'index'){
					$child['delete'] = 1; // 削除予約
					$item['realpath'] = $child['realpath']; // realpath は .md のものを引き継ぐ
					$item['hoge'] = 1;
					break;
				}
			}
		}
		
		// 兄弟要素として同名.mdがあればそれを削除し、realpathのみ受け継ぐ
		if($last['name'] == $item['name']){
			$dirs[$index - 1]['delete'] = 1; // 削除予約
			$item['realpath'] = $last['realpath']; // realpath は .md のものを引き継ぐ
			$item['fuga'] = 1;
		}
	}
	
	// 削除処理
	$dirs = array_filter($dirs, function($a){
		// if($a['type'] == 'file' && $name_counts[$a['name']] >= 2){
		//	return false; // 削除する
		// }
		if(isset($a['delete']))return false; // 削除する
		return true;
	});
	$dirs = array_values($dirs);

	// 子についても適用
	foreach($dirs as &$item){
		if(isset($item['children'])){
			_cut_duplicated_items($item, $item['children']);
		}
	}
}

function _get_dirs($real_path, $web_path)
{
	$dirs = [];
	$dir = opendir($real_path);
	if(!$dir)return [];
	while (($name = readdir($dir)) !== false) {
		// カレントディレクトリとかは当然無視
		if($name == ".")continue;
		if($name == "..")continue;

		// その他無視情報
		if(preg_match('/^_/', $name))continue;

		// 詳細情報
		$item = [];
		$item['key'] = $name; // 10_fuga_ふが … ソートに使う
		$item['name'] = $name; // fuga
		$item['realpath'] = $real_path . '/' . $name; // /home/sites/clock-up.jp/hogehoge/10_fuga_ふが.md
		$item['webpath']  = $web_path  . '/' . $name; // /hogehoge/fuga

		// ちょっとした加工
		$item['name'] = preg_replace('/^[0-9]+\_/', '', $item['name']); // 先頭数字を除去 (01_)
		$item['name'] = preg_replace('/^[A-Za-z][A-Za-z0-9\-\.]*\_/', '', $item['name']); // 先頭キーワードを除去 (da.ta-base_)
		$item['webpath']  = preg_replace('/\/[0-9]+\_/', '/', $item['webpath']); // webpathのほうでも先頭数字は除去 (01_)
		$item['webpath']  = preg_replace('/\_[^\_\/]+$/', '', $item['webpath']); // webpathのほうでは後ろのキーワードを除去 (_データベース)

		// タイプ判別・子取得
		if(is_dir($item['realpath'])){ // ディレクトリ
			$item['type'] = 'dir';
			$item['children'] = _get_dirs($item['realpath'], $item['webpath']); // 子要素
		}
		else if(preg_match('/\.md$/', $item['name'])){ // .mdファイル
			$item['type'] = 'file';
			// 拡張子 .md は取り除く
			//$item['name'] = preg_replace('/\.md$/', '', $item['name']);
			//$item['webpath'] = preg_replace('/\.md$/', '', $item['webpath']);
			
			// 拡張子除去
			$item['key'] = preg_replace('/\.[A-Za-z0-9]+$/', '', $item['key']); // 拡張子を除去
			$item['name'] = preg_replace('/\.[A-Za-z0-9]+$/', '', $item['name']); // 拡張子を除去
			$item['webpath'] = preg_replace('/\.[A-Za-z0-9]+$/', '', $item['webpath']); // 拡張子を除去
		}
		else{ // それ以外は無視
			continue;
		}
		
		// $item['webpath']  = $item['webpath'];
		// 追加
		$dirs[] = $item;
	}
	closedir($dir);
	
	// 名前順に並べる
	usort($dirs, function($a, $b){
		$ret = strcmp($a['key'], $b['key']);
		if($ret == 0){
			$ret = -strcmp($a['type'], $b['type']); // file, dir の順に並べる
		}
		return $ret;
	});
	
	// 結果
	return $dirs;
}
