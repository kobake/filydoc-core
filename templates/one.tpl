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
					{if isAdminUser()}
					<div class="page-control clearfix">
						<a href="#" ng-click="editBegin();" id="page-edit">
							<i class="glyphicon glyphicon-edit"></i>Edit
						</a>
						{if !$page_writable}
							<div id="error-message-writable-right" class="error-message-right">
								#Warning: this file has no permission to write.
							</div>
						{/if}
					</div>
					{/if}

					<div>
						<div id="error-message" class="error-message">
							Empty2
						</div>

						<div class="content-header ng-non-bindable" style="position: relative;">
							<h1>
								{$metas['h1title']}
							</h1>
							<div id="edit-path-wrapper" style="position: absolute; left: 0px; top: 0px; width: 100%; padding-left: 4px; padding-right: 35px;">
								<input type="text" name="edit-path" id="edit-path" value="{$metas['h1titleEdit']}" style="margin-left: 16px; width: 100%; line-height: 20pt; font-size: 12pt; padding-left: 4px; padding-right: 4px;" />
							</div>
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
							<div class="ng-non-bindable">
							{$body nofilter}
							</div>
							<!-- アイテム追加ボタン -->
							{if isAdminUser() && isset($templateItem) && $templateItem['type'] === 'dir'}
								<div style="margin-left: 20px; margin-top: 16px;" id="index-items-bottom">
									<button type="button" class="btn btn-default" data-toggle="modal" data-target="#myModal" id="new-item" ng-click="newItemButton();">
										新規アイテム
									</button>
								</div>

								<!-- Modal -->
								<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
									<div class="modal-dialog">
										<div class="modal-content">
											<form>
												<div class="modal-header" style="border-bottom: none;">
													<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
													<h4 class="modal-title" id="myModalLabel">新規アイテム作成</h4>
												</div>
												<div style="display: none; margin-bottom: 8px; color: #d66; margin-left: 20px;" id="dlg-error-message">
													アイテム名を入力してください。
												</div>
												<div style="margin: 0px 20px;">
													<input type="text" class="form-control" id="new-item-name" name="new-item-name" value="" placeholder="新規アイテム名" />
												</div>
												<div class="modal-footer" style="border-top: none; padding-top: 0px;">
													<button type="button" class="btn btn-default" data-dismiss="modal">キャンセル</button>
													<button type="submit" class="btn btn-primary" id="new-item-submit" ng-click="newItemSubmit();">作成</button>
												</div>
											</form>
										</div>
									</div>
								</div>

								<script>
									// モーダル表示時にテキストボックスにフォーカスを移す
									jQuery('#myModal').on('shown.bs.modal', function () {
										jQuery('#new-item-name').focus();
									});

									// モーダル非表示時にテキストボックスをクリアする
									jQuery('#myModal').on('hidden.bs.modal', function () {
										jQuery('#dlg-error-message').hide();
										jQuery('#new-item-name').val('');
									});
								</script>
							{/if}
						</div>
						{* getSocialButtons() nofilter *}
						{getPageFoot()}
					</div>
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
