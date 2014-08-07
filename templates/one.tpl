<!DOCTYPE html>
<html ng-app="myApp">
<head>
	<meta charset="UTF-8" />
	<!-- 漢字 -->
	<title>{$metas['headtitle']}</title>
	{if $metas['description'] != ''}
		<meta name="description" content="{$metas['description']}" />
	{/if}

	<!-- CSS -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" />
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" />

	<!-- <link rel="stylesheet/less" type="text/css" href="{getWebCoreDir()}/css/simple-sidebar.less" /> -->
	<link rel="stylesheet" href="{getWebCoreDir()}/css/simple-sidebar.css" />
</head>
<body>
	<div id="wrapper">
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<!-- 本体 -->
		<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
		<div id="page-content-wrapper"><div><div id="right-wrapper">
			<section id="content-section">
				<div ng-controller="RightController">
					<div class="page-control">
						<a href="#" ng-click="editBegin();" id="page-edit">
							<i class="glyphicon glyphicon-edit"></i>Edit
						</a>
					</div>
					<div id="error-message">
						Error message
					</div>
					<div class="content-header">
						<h1>
							{$metas['h1title']}
						</h1>
					</div>
					<div class="page-content inset">
						<!-- 内部目次 -->
						<div class="toc">
							<div class="toc-title">
								Contents
							</div>
							<div class="toc-content">
								<ol>
								</ol>
							</div>
						</div>
						<div class="toc-dummy">
						</div>
						<!-- 本体 -->
						{$body nofilter}
					</div>
					{* getSocialButtons() nofilter *}
					{getPageFoot()}
				</div>
			</section>
		</div></div></div>
	</div>
	<script src="//cdnjs.cloudflare.com/ajax/libs/less.js/1.7.0/less.min.js"></script>
	<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
	<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

	<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
	<!-- Script for social -->
	<!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
	<!-- Google+ social button -->
	{*
	<script type="text/javascript">
		window.___gcfg = {lang: 'ja'};
		(function() {
			var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
			po.src = 'https://apis.google.com/js/platform.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		})();
	</script>
	*}

	<!-- Tumblr social button -->
	{*
	<script src="http://platform.tumblr.com/v1/share.js"></script>
	*}

</body>
</html>
