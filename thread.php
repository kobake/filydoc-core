<?php
/*
 * スレッド(pthreads)のテスト…をしたいところだが、環境構築が難しそうなので、とりあえずマルチプロセス
 */
/*
必要な設定
extension=php_pthreads.dll
 */
if(php_sapi_name() != 'cli'){
	print("This script is allowed to run only from command line.\n");
	exit(0);
}




// 取得したいURL一覧
$url_list = array();
$url_list[] = 'http://www.yahoo.co.jp/';
$url_list[] = 'http://www.google.co.jp/';
$url_list[] = 'http://www.goo.ne.jp/';

// HTML全体からtitleを取得
function html2title($html){
	// まずは UTF-8 で決め打ち
	$htmlEncoded = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
	$doc = new DOMDocument();
	@$doc->loadHTML($htmlEncoded);

	// metaからcharset検出
	$charset = '';
	$elements = $doc->getElementsByTagName("meta");
	for($i = 0; $i < $elements->length; $i++){
		$e = $elements->item($i);

		// charset属性をチェック
		// <meta charset="utf-8"/>
		$node = $e->attributes->getNamedItem("charset");
		if($node){
			$charset = $node->nodeValue;
			break;
		}

		// http-equiv属性をチェック
		// <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		$node = $e->attributes->getNamedItem("http-equiv");
		if($node && strcasecmp($node->nodeValue, 'content-type') == 0){
			$node = $e->attributes->getNamedItem("content");
			if($node && preg_match('/[\; ]charset ?\= ?([A-Za-z0-9\-\_]+)/', $node->nodeValue, $m)){
				$charset = $m[1];
				break;
			}
			continue;
		}
	}

	// 検出されたcharsetがUTF-8じゃなかったら
	if($charset !== '' && !preg_match('/^utf\-?8$/i', $charset)){
		// 文字コード変換し直して
		$htmlEncoded = mb_convert_encoding($html, 'HTML-ENTITIES', $charset);
		// DOMも構築し直す
		$doc = new DOMDocument();
		@$doc->loadHTML($htmlEncoded);
	}

	// title取得
	$elements = $doc->getElementsByTagName("title");
	for($i = 0; $i < $elements->length; $i++){
		$e = $elements->item($i);
		return $e->textContent;
	}

	// titleが見つからなかった場合
	return false;
}

// -- -- 実験コード -- -- //
if(false){
	// x-sjis の例
	$body = file_get_contents("http://homepage3.nifty.com/abe-hiroshi/");
	$title = html2title($body);
	echo "title = $title\n";

	// euc-jp の例
	$body = file_get_contents("http://d.hatena.ne.jp/");
	$title = html2title($body);
	echo "title = $title\n";

	// Shift_JIS の例
	$body = file_get_contents("http://www.tohoho-web.com/www.htm");
	$title = html2title($body);
	echo "title = $title\n";

	// UTF-8 の例
	$body = file_get_contents("http://qiita.com/");
	$title = html2title($body);
	echo "title = $title\n";
}

// wget並列実行
function go($url_list){
	$processes = array();
	foreach($url_list as $url){
		// wget起動
		$arg = escapeshellarg($url);
		$cmd = "wget -qO- $arg";
		$processes[] = array(
			'url' => $url,
			'handle' => popen($cmd, 'r'),
			'body' => ''
		);
	}

	// 全てのコマンドが終了するまでひたすら回す
	$total = count($processes);
	$done = 0;
	while(true){
		$cnt = 0;
		foreach($processes as &$process) {
			// 参照が切れているなら何もしない
			if(!$process['handle'])continue;

			// 処理をしたもののカウント
			$cnt++;

			// まだ終わってなかったら
			if (!feof($process['handle'])) {
				// 出力1行取得して格納
				$line = fgets($process['handle']);
				$line = rtrim($line);
				$process['body'] .= $line;
			}
			// 終わっていたら
			else {
				// 閉じて、参照も切る
				pclose($process['handle']);
				$process['handle'] = null;
				// ログ
				$done++;
				print("Done($done/$total): $url\n");
			}
		}
		// どのプロセスも処理を終了していたら
		if($cnt == 0) {
			break; // 抜ける
		}
	}

	// 結果格納
	print("saving..\n");
	foreach($processes as $i => $process){
		file_put_contents("{$i}.txt", $process['body']);
	}
	print("saved.\n");
}

// http://qiita.com/Hiraku/items/1c67b51040246efb4254
function go3($urls){
	//タイムアウト時間を決めておく
	$TIMEOUT = 10; //10秒

	/*
	 * 1) 準備
	 *  - curl_multiハンドラを用意
	 *  - 各リクエストに対応するcurlハンドラを用意
	 *    リクエスト分だけ必要
	 *    * レスポンスが必要な場合はRETURNTRANSFERオプションをtrueにしておくこと。
	 *  - 全てcurl_multiハンドラに追加
	 */
	$mh = curl_multi_init();

	foreach ($urls as $u) {
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => $u,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => $TIMEOUT,
			CURLOPT_CONNECTTIMEOUT => $TIMEOUT,
		));
		curl_multi_add_handle($mh, $ch);
	}


	/*
	 * 2) リクエストを開始する
	 *  - curl_multiでは即座に制御が戻る（レスポンスが返ってくるのを待たない）
	 *  - いきなり失敗するケースを考えてエラー処理を書いておく
	 *  - do～whileはlibcurl<7.20で必要
	 */
	do {
		$stat = curl_multi_exec($mh, $running); //multiリクエストスタート
	} while ($stat === CURLM_CALL_MULTI_PERFORM);
	if ( ! $running || $stat !== CURLM_OK) {
		throw new RuntimeException('リクエストが開始出来なかった');
	}

	/*
	 * 3) レスポンスをcurl_multi_selectで待つ
	 *  - 何かイベントがあったらループが進む
	 *    selectはイベントが起きるまでCPUをほとんど消費せずsleep状態になる
	 *  - どれか一つレスポンスが返ってきたらselectがsleepを中断して何か数字を返す。
	 *
	 */
	do switch (curl_multi_select($mh, $TIMEOUT)) { //イベントが発生するまでブロック
		// ->最悪$TIMEOUT秒待ち続ける。タイムアウトは全体で統一しておくと無駄がない

		case -1: //selectに失敗。 https://bugs.php.net/bug.php?id=61141
			usleep(10); //ちょっと待ってからretryするのがお作法らしい？
			do {
				$stat = curl_multi_exec($mh, $running);
			} while ($stat === CURLM_CALL_MULTI_PERFORM);
			continue 2;

		case 0:  //タイムアウト -> 必要に応じてエラー処理に入るべき
			continue 2; //ここではcontinueでリトライします。

		default: //どれかが成功 or 失敗した
			do {
				$stat = curl_multi_exec($mh, $running); //ステータスを更新
			} while ($stat === CURLM_CALL_MULTI_PERFORM);

			do if ($raised = curl_multi_info_read($mh, $remains)) {
				//変化のあったcurlハンドラを取得する
				$info = curl_getinfo($raised['handle']);
				echo "{$info['url']}: {$info['http_code']}\n";
				$response = curl_multi_getcontent($raised['handle']);

				if ($response === false) {
					//エラー。404などが返ってきている
					echo 'ERROR!!!', PHP_EOL;
				} else {
					//正常にレスポンス取得
					// echo $response, PHP_EOL;

					// 結果格納
					print("saving..\n");
					$file = preg_replace('/[\.\/\:]/', '', $info['url']);
					file_put_contents("{$file}.txt", $response);
					print("saved.\n");
				}
				curl_multi_remove_handle($mh, $raised['handle']);
				curl_close($raised['handle']);
			} while ($remains);
		//select前に全ての処理が終わっていたりすると
		//複数の結果が入っていることがあるのでループが必要

	} while ($running);
	echo 'finished', PHP_EOL;
	curl_multi_close($mh);
}

$urls = array(
	'http://www.google.co.jp',
	'http://www.yahoo.co.jp',
	'https://qiita.com',
);
go3($urls);

exit(0);

// アカウント指定でクローラー順次起動
$processes = array();
foreach($accounts as $account){
	$otherAccounts = array_filter($accounts, function($a) use($account){ return $a != $account; });
	$a = $account . '_' . implode('_', $otherAccounts);

	// 起動
	$cmd = "./Console/cake crawler run {$modeList} {$a}";
	DbLog("wrap cmd: $cmd");
	$processes[$account] = popen($cmd, 'r');

	// 起動
	while(!feof($processes[$account])){
		// 出力1行取得
		$line = fgets($processes[$account]);
		$line = rtrim($line);

		// 表示
		print("wrap[{$account}]: " . $line . "\n");

		// NEXT_OK が出たら、一旦抜けて、次のアカウント起動へ進む
		if($line === 'NEXT_OK')break;
	}
}

// 全てのコマンドが終了するまでひたすら回す
while(true){
	$cnt = 0;
	foreach($processes as $account => &$process) {
		// 参照が切れているなら何もしない
		if(!$process)continue;
		// 処理をしたもののカウント
		$cnt++;
		// まだ終わってなかったら
		if (!feof($process)) {
			// 出力1行取得して表示
			$line = fgets($process);
			$line = rtrim($line);

			// 表示
			print("wrap[{$account}]: " . $line . "\n");
		}
		// 終わっていたら
		else {
			// 閉じて、参照も切る
			pclose($process);
			$process = null;
		}
	}
	// どのプロセスも処理を終了していたら
	if($cnt == 0) {
		break; // 抜ける
	}
}