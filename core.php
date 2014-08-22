<?php

// 日本語対策 (これをしないと escapeshellarg が日本語を除外してしまう)
setlocale(LC_CTYPE, "en_US.UTF-8");
setDefaultTimezone('Asia/Tokyo');
mb_language('Japanese'); // mb_convert_encodingの挙動調整

// 定数定義
define('APP_ROOT', dirname(__FILE__));
define('DATA_ROOT', realpath(dirname(__FILE__) . '/../data'));
define('TMP_ROOT', realpath(dirname(__FILE__) . '/../tmp'));

// HTTPリクエスト
$uri_without_query = '';
if(isset($_SERVER['REQUEST_URI'])){
	$uri_without_query = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
	// *.xml, robots.txt の処理
	if(preg_match('/\.xml$/', $uri_without_query)){
		header('Content-type: text/xml; charset=UTF-8');
		print(file_get_contents(APP_ROOT . $uri_without_query));
		exit(0);
	}
	else if(preg_match('/robots\.txt$/', $uri_without_query)){
		header('Content-type: text/plain; charset=UTF-8');
		print(file_get_contents(APP_ROOT . $uri_without_query));
		exit(0);
	}
}

function setDefaultTimezone($timezone) {
    if (!ini_get('date.timezone')) {
        date_default_timezone_set($timezone);
    }
}
// #### 仮
function feedExists(){
	return file_exists(APP_ROOT . '/feed.xml');
}
function getSiteName(){ // get_dirs を呼ばないとダメ
	global $g_sitename;
	if(!isset($g_sitename)){
		return 'Filydoc';
	}
	return $g_sitename;
}
function getSiteUrl(){
	return "http://" . $_SERVER['SERVER_NAME'] . preg_replace('/\/index.php$/', '/', $_SERVER['SCRIPT_NAME']);
}
function getPageUrl(){
	$uri = $_SERVER['REQUEST_URI'];
	$uri = preg_replace('/\?.*/', '', $uri);
	$uri = preg_replace('/\.html$/', '', $uri);
	return "http://" . $_SERVER['SERVER_NAME'] . $uri;
}
function getOgpType(){
	if($_SERVER['REQUEST_URI'] == getWebRootDir() . '/')return 'website';
	return 'article';
}
function getPageTitle(){
	global $metas;
	return preg_replace('/^Top - /', '', $metas['headtitle']);
}
function getWebRootDir(){ // 例：/memo
	return preg_replace('/\/index\.php$/', '', $_SERVER['SCRIPT_NAME']); // SCRIPT_NAME … /memo/index.php   PHP_SELF … /memo/index.php/hoggehoge
}
function getWebCoreDir(){ // 例：/memo/filydoc-core
	return getWebRootDir() . "/filydoc-core";
}
function getCopyright(){
	global $g_copyright;
	if(!isset($g_copyright)){
		return '-';
	}
	return $g_copyright;
}
function getAnalytics(){
	global $g_ga;
	if(isset($g_ga) && $g_ga !== ''){
		$html = <<<"EOS"
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		ga('create', '{$g_ga}', 'auto');
		ga('send', 'pageview');
	</script>
EOS;
		return $html;
	}
	return '';
}
function getPageFoot(){
	$html = @file_get_contents(DATA_ROOT . '/_pagefoot.html');
	if($html === false)return '';
	return $html;
}

// 設定のロード
if(!file_exists(APP_ROOT . '/settings.php')){
	print("Error: settings.php not found. Make settings.php by settings.php.example.");
	exit(1);
}
else{
	require_once(APP_ROOT . '/settings.php');
}

// 各モジュール
require_once(APP_ROOT . '/php/00_log.php');
require_once(APP_ROOT . '/php/10_dirs.php');
require_once(APP_ROOT . '/php/20_resolve.php');
require_once(APP_ROOT . '/php/30_load_save.php');
require_once(APP_ROOT . '/php/40_title.php');
require_once(APP_ROOT . '/php/50_meta.php');
require_once(APP_ROOT . '/php/60_menu.php');
require_once(APP_ROOT . '/php/80_search.php');
require_once(APP_ROOT . '/php/90_smarty.php');
require_once(APP_ROOT . '/php/95_social.php');
if(GitHubSettings::ENABLED){
	require_once(APP_ROOT . '/php/96_github.php');
}
require_once(APP_ROOT . '/php/97_feed.php');
require_once(APP_ROOT . '/php/98_sites.php');

// Markdown Extra
require_once(APP_ROOT . '/php/libs/markdown.php');
//set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/php/libs/php-markdown');
//use \Michelf\Markdown;
require_once(APP_ROOT . '/php/libs/php-markdown/Michelf/MarkdownExtra.inc.php');

function frameContent()
{
	global $template;
	$smarty = MySmarty::getInstance();
	//	$smarty->display(preg_replace('/\\.php$/', '.tpl', $_SERVER['SCRIPT_FILENAME']));
	$smarty->display($template);
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// コマンドラインからの起動 (実験中)
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// Usage:
//   php core.php feed       : Generate feed.xml
if(php_sapi_name() == 'cli') {
	if(in_array('feed', $argv)){
		print("Feed generating...\n");
		generateFeed();
		print("Feed generated.\n");
		exit(0);
	}
	else if(in_array('sitemap', $argv)){
		print("Sitemap generating...\n");
		generateSitemap();
		print("Sitemap generated.\n");
		exit(0);
	}
	else{
		print("Usage: \n");
		print("  php core.php feed       : Generate feed.xml\n");
		print("  php core.php sitemap    : Generate sitemap.xml\n");
		print("\n");
	}
	exit(0);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// HTTPリクエスト解釈
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// セッション開始
session_start();

// 単一ページ表示かどうかを判定 (.html)
// REQUEST_URI  … /memo/search.html?q=hoge
// REDIRECT_URL … /memo/search.html (これは無い場合もある)
$one_flag = false;
if(preg_match('/\.html$/', $uri_without_query)){ //「.html」で終わる場合
	$one_flag = 'html';
}
else if(preg_match('/\.md$/', $uri_without_query)){ //「.md」で終わる場合
	$one_flag = 'md';
}

// 検索ページかどうかを判定
$search_flag = false;
if($uri_without_query == getWebRootDir() . '/search.html' || $uri_without_query == getWebRootDir() . '/search'){
	$search_flag = true;
}

// 検索でない場合、大文字URLを小文字に補正
if(!$search_flag){
	if(preg_match('/[A-Z]/', $uri_without_query)){
		$url = strtolower($uri_without_query);
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		exit(0);
	}
}

// ログインページかどうかを判定
$login_flag = false;
if($uri_without_query == getWebRootDir() . '/login.html' || $uri_without_query == getWebRootDir() . '/login'){
	$login_flag = true;
	// GitHubログイン機能が無効な場合はログインページ自体を表示しない
	if(!GitHubSettings::ENABLED){
		$url = getWebRootDir();
		header("Location: {$url}/");
		exit(0);
	}
}

// ログアウトページかどうかを判定
if($uri_without_query == getWebRootDir() . '/logout') {
	// セッションを破棄し、
	$_SESSION = array();
	session_destroy();
	// リダイレクト
	$url = getWebRootDir();
	header("Location: {$url}/");
	exit(0);
}

// GitHubログインかどうかを判定
if(GitHubSettings::ENABLED) {
	if ($uri_without_query == getWebRootDir() . '/login_github') {
		// 一旦セッションは破棄
		$_SESSION = array();
		session_destroy();
		session_start(); // 再開
		// OAuth2.0認証
		$github = new GitHub();
		$username = $github->signup();
		// ユーザ名が取得できたら、セッションに保存してリダイレクト
		$_SESSION['github_username'] = $username;
		$url = getWebRootDir();
		header("Location: {$url}/");
		exit(0);
	}
	if (!isset($_SESSION['github_username'])) {
		$_SESSION['github_username'] = '';
	}
}

// まず全階層を漁る (これは常に必要。単一ページの場合でもパス解決のために必要)
$dirs = [];
$jsitems = [];
$dirs = get_dirs();


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// 権限関連関数
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// 自分が管理者かどうか判定
function isAdminUser(){
	if(!GitHubSettings::ENABLED)return false;
	if($_SESSION['github_username'] == '')return false;
	$admin_accounts = explode(',', GitHubSettings::ADMIN_ACCOUNTS);
	if(in_array($_SESSION['github_username'], $admin_accounts)){
		return true;
	}
	else{
		return false;
	}
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// コンテンツロード
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// ### 要改善: AJAX処理により多重にコンテンツロードが走っている

// -- -- 生テキスト取得 -- -- //
// ディスパッチ処理
// ※セキュリティ：リアルファイルにアクセスするため、ホスト内のpublic_html以外のファイルが参照されないように気を付けること
parse_str($_SERVER['QUERY_STRING'], $query);
$templateItem = null;
if($search_flag){
	// 検索結果をmarkdown形式で取得
	$text = searchAndGenerateMarkdownText($query['q']);
}
else if($login_flag){
	$text = 'login';
}
else{
	$templateItem = resolveTemplateItem();
	// テキストロード
	$text = loadText($templateItem);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// PUTを受け取る場合
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
if($templateItem && $one_flag == 'md' && $_SERVER['REQUEST_METHOD'] == 'PUT') {
	header('Content-type: application/json; charset=UTF-8');
	// print(var_export($_SERVER, true) . "\n");
	// print(var_export($_POST, true) . "\n");

	// 管理者でなければPUTを受け付けない
	if(!isAdminUser()){
		$result = array(
			'result' => 'FAILURE',
			'error' => 'You are not administrator'
		);
		echo json_encode($result);
		exit(0);
	}

	// putデータ受け取り
	$input = file_get_contents("php://input");

	// json parse
	$data = json_decode($input, true);

	// 保存
	try{
		saveText($templateItem, $data['markdown']);
		$result = array(
			'result' => 'SUCCESS'
		);
	}
	catch(Exception $ex){
		$result = array(
			'result' => 'FAILURE',
			'error' => $ex->getMessage()
		);
	}

	// 結果
	echo json_encode($result);
	exit(0);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// そのまま出力する場合
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
$page_writable = $templateItem && is_writable($templateItem['realpath']);
if($one_flag == 'md'){
	header('Content-type: text/plain; charset=UTF-8');
	// 最初の行はwritableかどうか
	echo $page_writable ? "WRITABLE\n" : "NOT_WRITABLE\n";
	// 続けて中身
	echo $text;
	exit(0);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// コンテンツMETA情報
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// meta情報読み取り
global $metas;
$metas = readMetas($text);

// メタデフォルト値
$defaults = [];
$defaults['description'] = '';
if($search_flag){
	$defaults['headtitle'] = 'SearchResult: ' . $query['q'] . ' - ' . getSiteName();
	$defaults['h1title'] = 'SearchResult: ' . $query['q'];
}
else if($login_flag){
	$defaults['headtitle'] = 'Login';
	$defaults['h1title'] = 'Login';
}
else{
	$title_items = getTitleItems();
	$defaults['headtitle'] = getHeadTitle($title_items);
	$defaults['h1title'] = getH1Title($title_items);
}
foreach($defaults as $key => $value){
	if(!isset($metas[$key])){
		$metas[$key] = $value;
	}
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// コンテンツHTML変換
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
if(preg_match('/\.md$/', $templateItem['realpath']) || $templateItem['type'] === 'dir'){
	// 本文Markdown処理
	$body = Michelf\MarkdownExtra::defaultTransform($text);
}
elseif(preg_match('/\.java$/', $templateItem['realpath'])){
	$body = $text;
	
	// 前後改行を除去
	$body = trim($body);
	
	// タブを4スペースに変換
	$body = preg_replace('/\t/', "    ", $body);
	
	// Java色分け処理
	require(APP_ROOT . '/php/libs/geshi/geshi.php');
	$geshi =& new GeSHi($body, 'java');
	//$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
	$geshi->enable_keyword_links(false);

	$body = $geshi->parse_code();
	
	// $body = "<pre>{$body}</pre>";
}
else{
	// プレーン表示
	$body = $text;
	$body = preg_replace('/\t/', "    ", $body);
	$body = "<pre>{$body}</pre>";
	//$body = preg_replace('/\n/', "<br/>\n", $body);
	//$body = preg_replace('/ /', "&nbsp;", $body);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// コンテンツ加工
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// 本文自動リンク処理（Markdown形式）※""で囲まれているURLはAタグの可能性があるので、何もしない
//$body = preg_replace('/([^\"])(https?\:\/\/[\w\/\:%#\$&\?\(\)~\.=\+\-]+)/', '\1[\2](\2)', $body);
// phpinfo();exit(0);
if(!$search_flag){
	// URL文字列の途中にアンダースコアが含まれている場合は \ でエスケープする必要がある（気持ち悪い…）(Markdownの場合) (現在はMarkdown Extraなのでエスケープ処理不要)
	// 例：[http://www.iconj.com/iphone\_style\_icon\_generator.php](http://www.iconj.com/iphone_style_icon_generator.php)

	if(AutoLinkSettings::ENABLED){
		// <code>内では自動リンクしない
		if(true){
			$body2 = '';
			$start = 0;
			while(true){				
				// <code>範囲取得
				$codeBegin = mb_strpos($body, '<code>', $start);
				if($codeBegin === false)break;
				$codeEnd = mb_strpos($body, '</code>', $codeBegin);
				if($codeEnd === false)break;
				$codeEnd += 7;
				$code = mb_substr($body, $codeBegin, $codeEnd - $codeBegin);
				// 置換
				$body2 .= mb_substr($body, $start, $codeBegin - $start);
				$body2 .= str_replace('http', '&#x68;ttp', $code);
				// 次
				$start = $codeEnd;
			}
			// 残り
			$body2 .= mb_substr($body, $start);
			// 結果
			$body = $body2;
		}
		
		// 実験：はてな風自動リンク (title取得)
		//
		// http://hogehoge/:title みたいな表記があった場合、インターネット上から自動的にタイトルを取得して
		// <a href="http://hogehoge/">たいとる</a> のようなリンクに変換する
		// タイトルは tmp/sites.db にキャッシュされ、次回からはキャッシュを優先する
		$body = preg_replace_callback('/([^\"])(https?\:\/\/[\w\/\:\;%#\$&\?\(\)~\.=\+\-]+)/', function($m){
			// matched
			$left = $m[1];
			$url = $m[2];

			// flag
			$flag = ''; // ':title' or ':notitle' or ''
			if(preg_match('/\:(title|notitle)$/', $url, $m)){
				$flag = ':' . $m[1];
				$url = preg_replace('/\:(title|notitle)$/', '', $url);
			}

			// :notitle指定なら取得しない
			if($flag == ':notitle'){
				return "{$left}<a href=\"{$url}\">{$url}</a>"; // URLのままで表示
			}

			// flag指定が無い場合はAutoLinkSettings::AUTO_TITLEに従う
			if($flag == ''){
				if(!AutoLinkSettings::AUTO_TITLE){
					return "{$left}<a href=\"{$url}\">{$url}</a>"; // URLのままで表示
				}
			}

			// とりあえず仮
			$title = url2title($url);

			// 結果
			return "{$left}<a href=\"{$url}\">{$title}</a>";
		}, $body);

		// 本仮
		url2title_wait();
		$body = url2title_replace_others($body);
	}

	// 自動リンク
	// $body = preg_replace('/([^\"])(https?\:\/\/[\w\/\:\;%#\$&\?\(\)~\.=\+\-]+)/', '\1<a href="\2">\2</a>', $body);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// Smarty準備
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// Smarty処理
$smarty = MySmarty::getInstance();
$smarty->php_handling = Smarty::PHP_ALLOW;
$smarty->template_dir = APP_ROOT . '/.';
$smarty->compile_dir = TMP_ROOT . '/smarty/templates_c/';
$smarty->config_dir = TMP_ROOT . '/smarty/config/';
$smarty->cache_dir = TMP_ROOT . '/smarty/cache/';
$smarty->assign('metas', $metas);
$smarty->assign('dirs', $dirs);
$smarty->assign('GITHUB_ENABLED', GitHubSettings::ENABLED);
if(GitHubSettings::ENABLED) {
	$smarty->assign('username', $_SESSION['github_username']);
}
else{
	$smarty->assign('username', '');
}
$smarty->assign('page_writable', $page_writable);


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// 特殊ページ処理
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
if($login_flag){
	//$body = file_get_contents(APP_ROOT . '/templates/login.tpl');
	$body = $smarty->fetch(APP_ROOT . '/templates/login.tpl');
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// メニュー部構築
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// メニューHTML
global $g_items;
$g_items = array();
if(!$one_flag){
	$items_html = get_items_html($dirs);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// 全体構築
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
$smarty->assign('body', $body);
if(!$one_flag){
	// $smarty->assign('breadcrumb_html', $breadcrumb_html);
	$smarty->assign('items_html', $items_html);
	$smarty->assign('dirs_json', json_encode($dirs));
	$smarty->display(APP_ROOT . '/templates/frame.tpl');
}
else if($one_flag == 'html'){
	$smarty->display(APP_ROOT . '/templates/one.tpl');
}
