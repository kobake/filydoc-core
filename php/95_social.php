<?php
function _socialPocket(){
	return <<<EOS
<div class="button_pocket">
	<a data-pocket-label="pocket" data-pocket-count="horizontal" class="pocket-btn" data-lang="en"></a>
</div>
EOS;
}
function _socialGooglePlus(){
	return <<<EOS
	<div class="button_google">
		<div class="g-plusone" data-size="medium"></div>
	</div>
EOS;
}
function _socialHatena(){
	$url = getPageUrl();
	return <<<EOS
	<div class="button_hatena">
		<a href="http://b.hatena.ne.jp/entry/{$url}" class="hatena-bookmark-button" data-hatena-bookmark-layout="standard-balloon" data-hatena-bookmark-lang="ja" title="このエントリーをはてなブックマークに追加"><img src="http://b.st-hatena.com/images/entry-button/button-only@2x.png" alt="このエントリーをはてなブックマークに追加" width="20" height="20" style="border: none;" /></a>
	</div>
EOS;
}
function _socialTwitter(){
	return <<<EOS
	<a href="https://twitter.com/share" class="twitter-share-button" data-via="kobayan_tokyo" data-lang="ja">ツイート</a>
EOS;
}
function _socialFacebook(){
	$url = getPageUrl();
	return <<<EOS
	<div class="fb-like" data-href="{$url}" data-layout="button_count" data-action="like" data-show-faces="true" data-share="true"></div>
EOS;
}
function _socialTumblr(){
	return <<<EOS
	<span class="button_tumblr">
		<a href="http://www.tumblr.com/share" title="Share on Tumblr" style="display:inline-block; text-indent:-9999px; overflow:hidden; width:81px; height:20px; background:url('http://platform.tumblr.com/v1/share_1.png') top left no-repeat transparent;">Share on Tumblr</a>
	</span>
EOS;
}
function getSocialButtons(){
	return
		'<div class="social clearfix" style="margin-top: 16px;">'
		. _socialPocket()
		. _socialGooglePlus()
		. _socialHatena()
		. _socialTwitter()
		. _socialFacebook()
		. _socialTumblr()
		. '</div>';
}
