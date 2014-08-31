$(function () {
	$("#menu-toggle").click(function (e) {
		e.preventDefault();
		$("#wrapper").toggleClass("active");
	});

	// textareaでtab入力できるように。
	// http://d.hatena.ne.jp/hokaccha/20111028/1319814792
	jQuery(function(){
		jQuery(document).on('keydown', 'textarea', function(e) {
			if (e.keyCode === 9) {
				e.preventDefault();
				var elem = e.target;
				var val = elem.value;
				var pos = elem.selectionStart;
				elem.value = val.substr(0, pos) + '\t' + val.substr(pos, val.length);
				elem.setSelectionRange(pos + 1, pos + 1);
			}
		});
	});

	// 外部サイトへのリンクターゲットを_blankに変更
	$("a[href^='http']")
		.not("[href*='" + location.host + "']")
		.attr('target', '_blank');
	/*
	// スクロール
	$("#sidebar-wrapper").mouseenter(function(eo){
	});
	$("div#right-wrapper").mouseenter(function(eo){
	console.log("right-wrapper");
	$("#sidebar-wrapper").unmousewheel();
	});
	*/
	// マウスホイール
	var f = function (eo, delta, deltaX, deltaY) {
		var scrollTop = $(this).scrollTop();
		//var scrollHeight = $(this).attr("scrollHeight");
		//if (scrollTop == 0) return true;
		var scrollHeight = $(this).get(0).scrollHeight;
		var height = $(this).height();

		if (scrollHeight == 0) {
			return true; // スクロールする
		}

		// 未来のscrollTop
		var newScrollTop = scrollTop - deltaY;

		/*
		console.log(scrollTop + "," + scrollHeight + "," + height + "," + deltaY + "," + newScrollTop);
		console.log(eo);
		console.log(eo.currentTarget);
		console.log(this);
		*/

		if (newScrollTop + height > scrollHeight) {
			return false;
		}
		else if (newScrollTop < 0) {
			return false;
		}
		return true;
	};
	$("#sidebar-wrapper-out").mousewheel(f);


	//$("body").mousewheel(f);



	/*
	$("div").mousewheel(function(eo, delta, deltaX, deltaY) {
	// $("div").text(delta);
	margin = $(eo.currentTarget).css('margin-left');
	margin = margin.replace("px", "");
	margin = parseInt(margin);

	console.log("hoge " + eo.clientX + ", " + margin);
	console.log(eo.eventPhase);
	console.log(eo.currentTarget);
	console.log(eo);

	if(eo.clientX >= margin){
	return true;
	}
	else{
	return false;
	}
	console.log(eo.delegateTarget);
	console.log(eo.clientX);
	return true;
	});
	*/
});
