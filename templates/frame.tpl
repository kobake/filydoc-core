<!DOCTYPE html>
<html ng-app="myApp">
<head>
	<meta charset="UTF-8" />
	<!-- 漢字 -->
	<title>{getPageTitle()}</title>
	{if $metas['description'] != ''}
	<meta name="description" content="{$metas['description']}" />
	{/if}

	<!-- favicon -->
	<link rel="icon" type="image/png" href="/favicon.png" />

	<!-- OGP -->
	<meta property="og:site_name" content="{getSiteName()}" data-webroot="{getWebRootDir()}" />
	<meta property="og:title" content="{getPageTitle()}" />
	<meta property="og:type" content="{getOgpType()}" />
	<meta property="og:url" content="{getPageUrl()}" />
	<meta property="og:image" content="{getSiteUrl()}ogp.png" />

	<!-- CSS -->
	<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" />
	<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" />
	<link rel="stylesheet" href="{getWebCoreDir()}/libs/jquery.treeview/jquery.treeview.css" />

	<!-- <link rel="stylesheet/less" type="text/css" href="{getWebCoreDir()}/css/simple-sidebar.less" /> -->
	<link rel="stylesheet" href="{getWebCoreDir()}/css/simple-sidebar.css" />

	<!-- Feed -->
	{if feedExists()}
	<link href="{getWebCoreDir()}/feed.xml" rel="alternate" title="Atom" type="application/atom+xml" />
	{/if}

	<style type="text/css">
	</style>
</head>
<body>
	<div id="fb-root"></div>
	{*
	{literal}
	<script>
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/ja_JP/sdk.js#xfbml=1&version=v2.0";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	</script>
	{/literal}
	*}
	<div id="wrapper">
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<!-- JS解釈 -->
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<script>
			window.menus = {$dirs_json nofilter};
		</script>
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<!-- ナビゲーション (上部をナビと呼ぶか左部をナビと呼ぶかは迷いどころである) -->
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<nav class="navbar navbar-default navbar-fixed-top role="navigation">
			<div class="container">
				<!-- Brand -->
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" rel="home" href="{getWebRootDir()}/" title="{getSiteName()} top" target="_self">{getSiteName()}</a>
				</div>

				<div class="collapse navbar-collapse navbar-ex1-collapse">
					<!-- Search -->
					<div class="col-sm-4 col-md-4" style="width: 300px;" ng-controller="SearchController"> <!-- 右寄せにする場合は pull-right -->
						<form class="navbar-form" role="search" /><!-- method="GET" action="/search"> -->
							<div class="input-group">
								<input type="text" class="form-control" placeholder="Search" name="q" id="search-keyword" ng-model="q">
								<div class="input-group-btn">
									<button class="btn btn-default" type="submit" ng-click="go()"><i class="glyphicon glyphicon-search"></i></button>
								</div>
							</div>
						</form>
					</div>

					<!-- ナビゲーションリンク等 -->
					<!--
						<ul class="nav navbar-nav">
						<li><a href="/all-topics/">/all</a></li>
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">Menu <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="#">Settings</a></li>
								<li><a href="#">Logout</a></li>
							</ul>
						</li>
						</ul>
					-->

					{if $GITHUB_ENABLED}
					<ul class="nav navbar-nav pull-right">
						{if $username == ''}
							<li>
								<a href="{getWebRootDir()}/login">Log in</a>
							</li>
						{else}
							<li>
								<a href="#" class="dropdown-toggle" data-toggle="dropdown">
									{$username} <span class="caret"></span>
								</a>
								<div class="dropdown-menu" style="min-width: 90px;">
									<div class="col-sm-12">
										<a href="{getWebRootDir()}/logout" target="_self">Log out</a>
									</div>
								</div>
							</li>
						{/if}
					</ul>
					{/if}

						<!--
					<div class="navbar-right">
						<div class="btn-group">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								Login <span class="caret"></span>
							</a>
							<div class="dropdown-menu" >
								<div class="col-sm-12">
									<div class="col-sm-12">
										Login
									</div>
									<div class="col-sm-12">
										<input type="text" placeholder="Uname or Email" onclick="return false;" class="form-control input-sm" id="inputError" />
									</div>
									<br/>
									<div class="col-sm-12">
										<input type="password" placeholder="Password" class="form-control input-sm" name="password" id="Password1" />
									</div>
									<div class="col-sm-12">
										<button type="submit" class="btn btn-success btn-sm">Sign in</button>
									</div>
								</div>
							</div>
						</div>
					</div>
					-->

				</div>

			</div>
		</nav>
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<!-- ナビゲーションより下部の全て -->
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<div class="clearfix" style="margin-top: 0px;">
			<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
			<!-- 本体 -->
			<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
			<div id="page-content-wrapper">
				<div>
					<div id="right-wrapper">
						<section id="content-section">
							<div class="page-content inset">
								{$body nofilter}
							</div>
						</section>
					</div>
				</div>
				<footer class="footer" id="footer">
					<div class="container">
						<p>
							{getCopyright()}
						</p>
						<p>
							Powered by <a href="http://filydoc.net">Filydoc</a>
						</p>
					</div>
				</footer>
			</div>
			<!-- ダミー -->
			<div ng-view></div>
			<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
			<!-- サイドバー -->
			<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
			<aside id="sidebar-wrapper-out">
				<div id="sidebar-wrapper">

					<div id="treecontrol">
						<div id="control-left">
							<input type="text" placeholder="Filter" name="filter" id="filter" ng-model="filter" />
						</div>
						<div id="control-right">
							<a title="Collapse the entire tree below" href="#"><img src="{getWebCoreDir()}/libs/jquery.treeview/images/minus.gif"><span>ColAll</span></a>
							<a title="Expand the entire tree below" href="#"><img src="{getWebCoreDir()}/libs/jquery.treeview/images/plus.gif"><span>ExpAll</span></a>
						</div>
					</div>

					<div id="tree-box">
						<div><a href="{getWebRootDir()}/"><span>Top</span></a></div>
						{$items_html}
					</div>
				</div>
			</aside>
		</div>
	</div> <!-- /wrapper -->
	<script src="//cdnjs.cloudflare.com/ajax/libs/less.js/1.7.0/less.min.js"></script>
	<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
	<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	<script src="{getWebCoreDir()}/libs/jquery.mousewheel.js"></script>
	<script src="{getWebCoreDir()}/libs/jquery.cookie/jquery.cookie.js"></script>
	<script src="{getWebCoreDir()}/libs/jquery.treeview/jquery.treeview.js" type="text/javascript"></script>
	<script src="{getWebCoreDir()}/libs/angularjs/angular.js"></script>
	<script src="{getWebCoreDir()}/libs/angularjs/angular-resource.js"></script>
	<script src="{getWebCoreDir()}/libs/angularjs/angular-route.js"></script>
	<script src="{getWebCoreDir()}/libs/footerFixed.js"></script>
	<script src="{getWebCoreDir()}/js/tree.js"></script>
	<script src="{getWebCoreDir()}/js/app.js"></script>
	<script src="{getWebCoreDir()}/js/other.js"></script>
	{getAnalytics() nofilter}

</body>
</html>
