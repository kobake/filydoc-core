<?php

// 日本語対策 (これをしないと escapeshellarg が日本語を除外してしまう)
setlocale(LC_CTYPE, "en_US.UTF-8");

// 定数定義
define('APP_ROOT', dirname(__FILE__));
define('DATA_ROOT', realpath(dirname(__FILE__) . '/../data'));
define('TMP_ROOT', realpath(dirname(__FILE__) . '/../tmp'));

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

// 各モジュール
require_once(APP_ROOT . '/php/00_log.php');
require_once(APP_ROOT . '/php/10_dirs.php');
require_once(APP_ROOT . '/php/20_resolve.php');
require_once(APP_ROOT . '/php/30_load.php');
require_once(APP_ROOT . '/php/40_title.php');
require_once(APP_ROOT . '/php/50_meta.php');
require_once(APP_ROOT . '/php/60_menu.php');
require_once(APP_ROOT . '/php/80_search.php');
require_once(APP_ROOT . '/php/90_smarty.php');
require_once(APP_ROOT . '/php/95_social.php');

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
	$one_flag = true;
}

// 検索ページかどうかを判定
$search_flag = false;
if($uri_without_query == getWebRootDir() . '/search.html' || $uri_without_query == getWebRootDir() . '/search'){
	$search_flag = true;
}

// まず全階層を漁る (これは常に必要。単一ページの場合でもパス解決のために必要)
$dirs = [];
$jsitems = [];
$dirs = get_dirs();

// ディスパッチ処理
// ※セキュリティ：リアルファイルにアクセスするため、ホスト内のpublic_html以外のファイルが参照されないように気を付けること
parse_str($_SERVER['QUERY_STRING'], $query);
if($search_flag){
	// 検索結果をmarkdown形式で取得
	$text = searchAndGenerateMarkdownText($query['q']);
}
else{
	$templateItem = resolveTemplateItem();
	// テキストロード
	$text = loadText($templateItem);
}

// meta情報読み取り
global $metas;
$metas = readMetas($text);

// メタデフォルト値
$defaults = [];
$defaults['description'] = '';
if($search_flag){
	$defaults['headtitle'] = '検索結果: ' . $query['q'] . ' - ' . getSiteName();
	$defaults['h1title'] = '検索結果: ' . $query['q'];
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

// 本文Markdown処理
require_once(APP_ROOT . '/php/libs/markdown.php');
$body = markdown($text);

// 本文自動リンク処理（Markdown形式）※""で囲まれているURLはAタグの可能性があるので、何もしない
//$body = preg_replace('/([^\"])(https?\:\/\/[\w\/\:%#\$&\?\(\)~\.=\+\-]+)/', '\1[\2](\2)', $body);
if(!$search_flag){
	$body = preg_replace('/([^\"])(https?\:\/\/[\w\/\:\;%#\$&\?\(\)~\.=\+\-]+)/', '\1<a href="\2">\2</a>', $body);
}

// メニューHTML
global $g_items;
$g_items = array();
if(!$one_flag){
	$items_html = get_items_html($dirs);
}

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
if(!$one_flag){
	// $smarty->assign('breadcrumb_html', $breadcrumb_html);
	$smarty->assign('items_html', $items_html);
	$smarty->assign('dirs_json', json_encode($dirs));
	$smarty->display(APP_ROOT . '/templates/frame.tpl');
}
else{
	$smarty->display(APP_ROOT . '/templates/one.tpl');
}
