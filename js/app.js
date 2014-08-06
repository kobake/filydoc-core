// TOC構築
function generateToc($compile, $scope){
	console.log("TOC");
	// まずクリア
	$('.toc-content ol').html("");
	var ol = $('.toc-content ol');
	// h2検出
	var cnt = 0;
	$('.page-content h2').each(function(){
		cnt++;
		var h2 = $(this);
		// テキスト
		var text = h2.text();
		// アンカー
		var anchor = generateAnchor(text);
		var span = '<span id="' + anchor + '">&nbsp;</span>';
		h2.append(span);
		// 項目追加
		//var li = jQuery('<li><a href="#' + anchor + '" target="_self">' + h2.text() + '</a></li>');
		var li = jQuery($compile('<li><a ng-href="#' + anchor + '" href="">' + h2.text() + '</a></li>')($scope));
		ol.append(li);
	});
	// 目次がなければ(または1個以下なら)目次枠自体を表示しない
	if(cnt <= 1){
		$('.toc').hide();
		$('.toc-dummy').show();
	}
	else{
		$('.toc').show();
		$('.toc-dummy').hide();
	}
}

// アンカー文字列構築
function generateAnchor(str){
	return str.replace(/[ \#\"\$\!\%\[\]\:\;\?]/g, '_');
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// アイテム一覧データの展開
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
window.g_inited = false;
function initItems() {
	// 一度だけ実行
	if (g_inited) return;
	g_inited = true;

	// sitename
	window.g_webroot = $('meta[property="og:site_name"]').data('webroot'); // 例：/memo
	window.g_sitename = $('meta[property="og:site_name"]').attr('content'); // 例：clock-up-memo

	// webpathルート
	window.g_top = { name: 'Top', type: 'dir', webpath: '/', children: window.menus };

	// webpath2item構築
	window.g_webpath2item = {};
	function generate_webpath2item(menu) {
		window.g_webpath2item[menu.webpath] = menu;
		if (menu.children) {
			for (var i = 0; i < menu.children.length; i++) {
				generate_webpath2item(menu.children[i]);
			}
		}
	}
	generate_webpath2item(window.g_top);

	// li構築
	var webroot_without_slash = window.g_webroot.replace(/\/$/, '');
	function findLi(item){
		return $('#tree-box a[href="' + webroot_without_slash + item.webpath + '"]').parent('li');
	}
	function refer_all_li(item) {
		// itemに関連付くliを見つけておく
		item.li = findLi(item);
		// 子についても再帰処理
		if (item.children) {
			for (var i = 0; i < item.children.length; i++) {
				refer_all_li(item.children[i]);
			}
		}
	}
	refer_all_li(window.g_top);
}
$(function () {
	initItems();
});

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// Search controller
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
function SearchController($scope, $location) {
	// 検索
	$scope.go = function () {
		// $location.path('/search?q=' + $scope.q);
		//console.log("------");
		//console.log("go");
		//console.log($scope.q); // これ何故か途中で消失することある…
		//var q = $scope.q;
		var q = jQuery('#search-keyword').val();
		$location.path(window.g_webroot + '/search').search('q', q);
	};

	// $scope.go = function (path) {
	// 	$location.path(path);
	// };
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// パス加工
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// @param  String ext [in] 拡張子。例：md  例：html
// @return String AJAX取得用パス。例：/Windows/Excel.html
function getWebPathForAjax($location, ext){
	// webpath
	var webpath = $location.path(); // "/memo/Windows/Excel" みたいな感じ。
	webpath = webpath.replace(/\/$/, '');
	if (webpath == '') {
		webpath = window.g_webroot;
	}
	console.log("webpath = " + webpath);

	// webpath2 "/Windows/Excel"
	var webpath2 = webpath;
	if(webpath2.indexOf(window.g_webroot) == 0){
		webpath2 = webpath2.replace(new RegExp(window.g_webroot), ''); // "/Windows/Excel"
	}
	console.log("webpath2 = " + webpath2);

	// ajaxpath
	var ajaxpath = webpath;
	if (ajaxpath == window.g_webroot) {
		ajaxpath = window.g_webroot + '/index';
	}
	ajaxpath += '.' + ext;
	console.log("ajaxpath = " + ajaxpath);

	// 検索キーワード
	var q = $location.search().q;
	if (typeof q != 'undefined' && q !== '') {
		ajaxpath += '?q=' + q;
		// 検索ボックスに q を入れておく
		$('#search-keyword').val(q);
	}
	else{
		jQuery('#search-keyword').val('');
	}

	//$location.
	var menu = window.g_webpath2item[webpath2];
	if (menu) {
		if (menu.type == 'dir') {
			ajaxpath = (window.g_webroot + webpath2 + '/index.' + ext).replace('//', '/'); // 例：/memo/Chrome.html
		}
	}
	console.log("url = " + $location.url());   // スラッシュから始まる。こっちはURLエンコーディングされてる。あと、ハッシュが付いてる。
	console.log("path = " + $location.path()); // スラッシュから始まる。こっちは生文字列。ハッシュは付いてない。★こっちをパンくずに使う
	console.log("absUrl = " + $location.absUrl()); // httpから始まる。クエリ文字列も付いてくる
	console.log("search = " + $location.search()); // クエリ文字列をハッシュにしたやつ。 {q: 'ほげ'} みたいな感じ。
	console.log($location.search());
	console.log("search.q = " + $location.search().q);
//	console.log($location.search('q'));
	return ajaxpath;
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// Right controller
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
function RightController($scope, $location, $compile, $http){
	// 編集開始
	$scope.editBegin = function() {
		console.log("editBegin.");

		// 既に編集モードなら何もしない
		if (jQuery('#edit-textarea').size() >= 1) {
			return;
		}

		// 編集領域の部品を作る
		var frame = jQuery('<div style="margin-left: -40px;"></div>');
		frame.append(jQuery('<div><textarea id="edit-textarea" style="width:100%; height: 300px;"></textarea></div>'));
		var bottom = jQuery('<div style="margin-top: 2px;"></div>');
		bottom.append(jQuery('<button class="btn btn-default" ng-click="editSave();" style="width: 100px; margin-right: 8px;">Save</button>'));
		bottom.append(jQuery('<button class="btn btn-default" ng-click="editCancel();" style="width: 100px;">Cancel</button>'));
		frame.append(bottom);

		// コンパイル
		var html = $compile(frame[0].outerHTML)($scope);

		// 差し替え
		var content = jQuery('.page-content');
		$scope.original_html = content.html();
		content.html(html);

		// フッタ
		window.footerFixed();

		// 内容取得
		$scope.load();
	}

	// ロード
	$scope.load = function(){
		// 内容取得
		var ajaxpath = getWebPathForAjax($location, 'md');
		console.log("Get content from " + ajaxpath);
		$http.get(ajaxpath)
			.error(function (data, status, headers, config) {
				$('#edit-textarea').val("error");
			})
			.success(function (data, status, headers, config) {
				$('#edit-textarea').val(data);
			});
	};

	// 編集確定
	$scope.editSave = function(){
		console.log("editSave.");
	};

	// 編集キャンセル
	$scope.editCancel = function(){
		console.log("editCancel.");
		var content = jQuery('.page-content');
		content.html($scope.original_html);
		delete($scope.original_html);
	};
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// もうちょっと大きいモジュールおよびコントローラの例
// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- //
// myApp.service というモジュールを作成
// ここに greeter と user が定義される
angular.module('myApp.service', []);

// myApp.directive というモジュールを作成
angular.module('myApp.directive', []);

// myApp.filter というモジュールを作成
angular.module('myApp.filter', []);

// myApp というモジュールを作成 (子要素として 3つのモジュールを含む)
// service に定義されている user が来る？
// ★必要なプロバイダ（ngRoute等）をここで登録しておく
app = angular.module('myApp', ['ngRoute', 'myApp.service', 'myApp.directive', 'myApp.filter']);

// 汎用ページローダ
app.controller('PageController', function ($scope, $http, $location, $compile, $window) {
	// 上のinitItemよりこっちが先に呼ばれちゃうことがあるので、ここでも initItems を呼ぶ
	initItems();

	// リンクをクリックされるとここが呼ばれる
	//$scope.abc = 'JJAAVVAA' + Math.random();
	/*
	console.log("==HOGE==");
	console.log("$location.absUrl() = " + $location.absUrl());
	console.log("$location.path() = " + $location.path());
	*/
	// ここで非同期通信
	var ajaxpath = getWebPathForAjax($location, 'html');
	console.log("Get content from " + ajaxpath);
	$http.get(ajaxpath)
		.error(function (data, status, headers, config) {
			// コンテンツ更新
			$('#content-section').html("<div style='margin:20px;'>Content not found</div>");
			// タイトル更新
			$('head title').text('not found - ' + window.g_sitename);
		})
		.success(function (data, status, headers, config) {
			// コンテンツ更新
			//$('#content-section').replaceWith($(data).find('#content-section'));
			var html = $(data).find('#content-section').html();
			$('#content-section').html($compile(html)($scope));

			// 目次
			generateToc($compile, $scope);

			// analytics
			if($window['ga']){
				console.log("new analytics");
				$window.ga('send', 'pageview', { page: $location.path() }); //new
			}
			if($window['_gaq']) {
				console.log("old analytics");
				$window._gaq.push(['_trackPageview', $location.path()]); //old
			}

			// -- ソーシャル -- //
			if(true){
				// pocket
				jQuery('#pocket-btn-js').remove();
				!function(d,i){
					if(!d.getElementById(i)){
						var j = d.createElement("script");
						j.id = i;
						j.src = "https://widgets.getpocket.com/v1/j/btn.js?v=1";
						var w = d.getElementById(i);
						d.body.appendChild(j);
					}
				}(document,"pocket-btn-js");
				// twitter
				jQuery('#twitter-wjs').remove();
				!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
				// hatena
				jQuery('#hatena-btn-js').remove();
				!function(d,i){
					if(!d.getElementById(i)){
						var j = d.createElement("script");
						j.id = i;
						j.src = "http://b.st-hatena.com/js/bookmark_button.js";
						j.charset = 'utf-8';
						j.defer = true;
						j.async = true;
						var w = d.getElementById(i);
						d.body.appendChild(j);
					}
				}(document,"hatena-btn-js");
				// google plus
				jQuery('#google-btn-js').remove();
				window.___gcfg = {lang: 'ja'};
				(function() {
					var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
					po.id = 'google-btn-js';
					po.src = 'https://apis.google.com/js/platform.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
				})();
				// facebook
				FB = null;
				jQuery('#fb-root').html("");
				jQuery('#facebook-jssdk').remove();
				(function(d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0];
					if (d.getElementById(id)) return;
					js = d.createElement(s); js.id = id;
					js.src = "//connect.facebook.net/ja_JP/sdk.js#xfbml=1&version=v2.0";
					js.defer = true;
					js.async = true;
					fjs.parentNode.insertBefore(js, fjs);
					document.body.appendChild(js);
				}(document, 'script', 'facebook-jssdk'));
				// tumblr
				jQuery('#tumblr-btn-js').remove();
				!function(d,i){
					if(!d.getElementById(i)){
						var j = d.createElement("script");
						j.id = i;
						j.src = "http://platform.tumblr.com/v1/share.js";
						j.charset = 'utf-8';
						j.defer = true;
						j.async = true;
						var w = d.getElementById(i);
						d.body.appendChild(j);
					}
				}(document,"tumblr-btn-js");
			}

			// フッタ
			window.footerFixed();

			// ハッシュ
			var h = $location.hash();
			console.log("HASH = " + h);
			h = generateAnchor(h);
			console.log("HASH2 = " + h);

			// ハッシュに一致する h2 にスタイルを追加
			$('.page-content h2').removeClass('h2-active');
			$('.page-content h2 span[id="' + h + '"]').parent().addClass('h2-active');
			if($('#' + h).size() >= 1) {
				$(document.body).scrollTop($('#' + h).offset().top);
			}
			else{
				$(document.body).scrollTop(0);
			}

			// 外部サイトへのリンクターゲットを_blankに変更
			$("#content-section a[href^='http']")
				.not("[href*='" + location.host + "']")
				.attr('target', '_blank');

			// タイトル更新
			var title = window.g_sitename;
			if(!title){
				title = 'Filydoc';
			}
			var m = data.match(/<title>(.*)<\/title>/);
			if (m) {
				title = m[1];
			}
			$('head title').text(title);
			// ツリーアイテムアクティブ化
			$('#tree-box li a').removeClass('active');
			$('#tree-box li').removeClass('active');
			$('#tree-box > div > a[href="/"]').removeClass('active');
			var a = $('#tree-box li a[href="' + $location.path() + '"]');
			if (a.size() == 0) {
				$('#tree-box > div > a[href="/"]').addClass('active');
				var div = $('#sidebar-wrapper-out');
				div.scrollTop(0);
			}
			else {
				a.addClass('active'); // a.active
				a.parent().addClass('active'); // li.active
				// 親のツリー開く
				var this_li = a.parent();
				var top_li = expandLiParents(this_li);

				// 自分のツリー開く
				expandLi(this_li);
				
				// スクロール
				console.log("====SCROLL====");
				console.log(this_li);
				console.log(top_li);
				treeScroll(this_li, top_li);
			}
			window.already_scrolled = true;
		});
});

// ★ルーティング設定
app.config(['$routeProvider', function ($routeProvider) {
	$routeProvider
		//.when('/asm/', { templateUrl: '/test.txt' })
		//.when('/chrome', { templateUrl: '/test2.txt' })
		.otherwise({ controller: 'PageController', template:'' })//, template: '<div id="loading"><img src="/_img/ajax-loader.gif" /></div>', reloadOnSearch: true })
		;
	//.when('/bbb/', { controller: 'BbbController', templateUrl: 'bbb.html' });
} ]);

// ★ここでlocation設定 (これを行うと、すべてのリンクが Angular っぽい挙動になる
///*
app.config(['$locationProvider', function($locationProvider){
	$locationProvider.html5Mode(true);
}]);
//*/

// ★コントローラ
app.controller('GetRequestCtrl', function ($scope, $rootScope, $http, $location) {
	var load = function () {
		console.log('loading...' + $rootScope.appUrl);
	};

	load();

	// ng-click="addPost()
	$scope.addPost = function () {
		$location.path("/new"); // 非同期で/newへ遷移
	};
});

// ★モジュール実行
app.run(function () {
});
