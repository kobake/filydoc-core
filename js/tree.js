function expandLi(li) {
	var ul = li.find('> ul');
	if (ul.size() >= 1) {
		li.find('> ul').show();
		li.replaceClass('expandable', 'collapsable');
		li.replaceClass('lastExpandable', 'lastCollapsable');
		li.find('> .hitarea').replaceClass('expandable-hitarea', 'collapsable-hitarea');
		li.find('> .hitarea').replaceClass('lastExpandable-hitarea', 'lastCollapsable-hitarea');
		// フィルタ等で子がhideされている場合はここでshowして戻す
		ul.find('> li').show();
	}
}

// 一番親の li を返す
function expandLiParents(this_li) {
	this_li.parents('ul').show();
	var top_li = this_li;
	var li = this_li;
	while (true) {
		li = li.parent().parent();
		if (li.prop('tagName') != 'LI') break;
		top_li = li;
		li.show();
		li.replaceClass('expandable', 'collapsable');
		li.replaceClass('lastExpandable', 'lastCollapsable');
		li.find('> .hitarea').replaceClass('expandable-hitarea', 'collapsable-hitarea');
		li.find('> .hitarea').replaceClass('lastExpandable-hitarea', 'lastCollapsable-hitarea');
	}
	return top_li;
}

// 全閉じ
function collapseAll() {
	$('#tree-box ul ul').hide();
	$('#tree-box ul li').replaceClass('collapsable', 'expandable');
	$('#tree-box ul li .hitarea').replaceClass('collapsable-hitarea', 'expandable-hitarea');
}

// アイテムが見える位置までスクロール
function treeScroll(this_li, top_li)
{
	var this_a = this_li.find('> a');

	var isElementInRange = function (element, range) {
		if (element.offset().top + element.height() > range.offset().top + range.height()) return false;
		if (element.offset().top < range.offset().top + 28 + 8) return false;
		return true;
	}
	var div = $('#sidebar-wrapper-out');
	// 親要素が見えるようにスクロール
	if (!window.already_scrolled) {// && !isElementInRange(top_li, div)) {
		//div.scrollTop(div.scrollTop() + top_li.offset().top - 51 - 28 - 8);
		div.scrollTop(div.scrollTop() + top_li.position().top - 28 - 8);
	}
	// 子が隠れてしまっていたら、子要素が見えるようにスクロール
	if (!isElementInRange(this_a, div)) {
		//div.scrollTop(div.scrollTop() + this_a.offset().top - 51 - 28 - 8);
		div.scrollTop(div.scrollTop() + this_a.position().top - 28 - 8);
	}
}

$(function () {
	// ツリービュー
	$('#tree-box > ul').treeview({
		control: "#treecontrol",
		collapsed: true
		//persist: "cookie"
		//cookieId: "treeview-black"
	});
	// fourth example
	/*
	$("#black, #gray").treeview({
		control: "#treecontrol",
		persist: "cookie",
		cookieId: "treeview-black"
	});
	*/
	// 基本は全閉じ
	//$('#tree-box ul').hide();

	// コントロールボックスの幅調整
	var getScrollBarWidth = function (){
		var helper = document.createElement('div');
		helper.style = "width: 100px; height: 100px; overflow:hidden;"
		document.body.appendChild(helper);
		var bigger = helper.clientWidth;
		helper.style.overflow = "scroll";
		var smaller = helper.clientWidth;
		document.body.removeChild(helper);
		return(bigger - smaller);
	};
	var w = $('#sidebar-wrapper-out').width();
	w -= getScrollBarWidth();
	$('#treecontrol').outerWidth(w);

	// フィルタ
	$("#filter").bind("change keyup input paste", function () {
		var filter = $("#filter").val().toLowerCase();
		// $('h1').text(filter);
		if (filter === "") {
			// -- -- 元の表示に戻す -- -- //
			// まず全閉じ
			collapseAll();

			// li は全show
			$('.treeview li').show();

			// 現在のactiveアイテムを探す
			var this_li = $('#tree-box li.active');

			if (this_li.size() > 0) {
				// 親のツリー開く
				var top_li = expandLiParents(this_li);

				// 自分のツリー開く
				expandLi(this_li);

				// スクロール
				treeScroll(this_li, top_li);
			}
		}
		else {
			// -- -- フィルタ結果 -- -- //
			// まず全閉じ
			collapseAll();
			// 全アイテムについて表示・非表示を判定する
			function filtering(item) {
				// 自分についての判定
				var li = item.li;
				if (li.size() > 0) {
					// console.log(item.keywords);
					if (item.keywords.indexOf(filter) >= 0) {
						li.show();
						expandLiParents(li);
					}
					else {
						li.hide();
					}
				}
				// 子についても再帰処理
				if (item.children) {
					for (var i = 0; i < item.children.length; i++) {
						filtering(item.children[i]);
					}
				}
			}
			filtering(window.g_top);
			/*
			$('.treeview li').each(function () {
				var li = $(this);
				if (li.text().toLowerCase().indexOf(filter) >= 0) {
					li.show();
					expandLiParents(li);
				}
				else {
					li.hide();
				}
			});
			*/
		}
	});
});
