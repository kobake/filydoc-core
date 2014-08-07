<?php

// 日本語対策 (これをしないと escapeshellarg が日本語を除外してしまう)
setlocale(LC_CTYPE, "en_US.UTF-8");
setDefaultTimezone('Asia/Tokyo');

// 定数定義
define('APP_ROOT', dirname(__FILE__));
define('DATA_ROOT', realpath(dirname(__FILE__) . '/../data'));
define('TMP_ROOT', realpath(dirname(__FILE__) . '/../tmp'));

// セッション開始
session_start();

function setDefaultTimezone($timezone) {
    if (!ini_get('date.timezone')) {
        date_default_timezone_set($timezone);
    }
}
// #### 仮
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

// 単一ページ表示かどうかを判定 (.html)
// REQUEST_URI  … /memo/search.html?q=hoge
// REDIRECT_URL … /memo/search.html (これは無い場合もある)
$uri_without_query = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
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

// ログインページかどうかを判定
$login_flag = false;
if($uri_without_query == getWebRootDir() . '/login.html' || $uri_without_query == getWebRootDir() . '/login'){
	$login_flag = true;
	// GitHubログイン機能が無効な場合はログインページ自体を表示しない
	if(!GitHubSettings::ENABLED){
		header('location: /');
		exit(0);
	}
}

// ログアウトページかどうかを判定
if($uri_without_query == getWebRootDir() . '/logout') {
	// セッションを破棄し、
	$_SESSION = array();
	session_destroy();
	// リダイレクト
	header("Location: /");
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
		header("Location: /");
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
// 本文Markdown処理
$body = Michelf\MarkdownExtra::defaultTransform($text);


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// コンテンツ加工
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// 本文自動リンク処理（Markdown形式）※""で囲まれているURLはAタグの可能性があるので、何もしない
//$body = preg_replace('/([^\"])(https?\:\/\/[\w\/\:%#\$&\?\(\)~\.=\+\-]+)/', '\1[\2](\2)', $body);
if(!$search_flag){
	// URL文字列の途中にアンダースコアが含まれている場合は \ でエスケープする必要がある（気持ち悪い…）
	// 例：[http://www.iconj.com/iphone\_style\_icon\_generator.php](http://www.iconj.com/iphone_style_icon_generator.php)
	$body = preg_replace('/([^\"])(https?\:\/\/[\w\/\:\;%#\$&\?\(\)~\.=\+\-]+)/', '\1<a href="\2">\2</a>', $body);
}


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// 特殊ページ処理
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
if($login_flag){
	$body = file_get_contents(APP_ROOT . '/templates/login.tpl');
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
// Smarty処理
$smarty = MySmarty::getInstance();
$smarty->php_handling = Smarty::PHP_ALLOW;
$smarty->template_dir = APP_ROOT . '/.';
$smarty->compile_dir = TMP_ROOT . '/smarty/templates_c/';
$smarty->config_dir = TMP_ROOT . '/smarty/config/';
$smarty->cache_dir = TMP_ROOT . '/smarty/cache/';
$smarty->assign('metas', $metas);
$smarty->assign('body', $body);
$smarty->assign('dirs', $dirs);
$smarty->assign('GITHUB_ENABLED', GitHubSettings::ENABLED);
if(GitHubSettings::ENABLED) {
	$smarty->assign('username', $_SESSION['github_username']);
}
else{
	$smarty->assign('username', '');
}
$smarty->assign('page_writable', $page_writable);
if(!$one_flag){
	// $smarty->assign('breadcrumb_html', $breadcrumb_html);
	$smarty->assign('items_html', $items_html);
	$smarty->assign('dirs_json', json_encode($dirs));
	$smarty->display(APP_ROOT . '/templates/frame.tpl');
}
else if($one_flag == 'html'){
	$smarty->display(APP_ROOT . '/templates/one.tpl');
}
