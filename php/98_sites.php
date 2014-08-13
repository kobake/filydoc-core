<?php

function error_log_multi($str){
	$a = explode("\n", $str);
	foreach($a as $line)error_log($line);
}

/*
 * サイト名管理
 */
class SiteManager{
	// Singleton
	public static function instance(){
		global $g_siteManager;
		if(!$g_siteManager){
			$g_siteManager = new SiteManager();
		}
		return $g_siteManager;
	}

	private $m_urlStates = array();

	public function SiteManager(){
	}

	public function pushUrl($url){
		// 登録済みなら、それを返す
		if(isset($this->m_urlStates[$url])){
			return $this->m_urlStates[$url]['title_future'];
		}
		// 登録
		$index = count($this->m_urlStates);
		$state = array(
			'url' => $url,
			'title_future' => "<FUTURE_SITE_TITLE:{$index}>",
			'ch' => null,
			'title' => $url, // デフォルト
			'state' => 0
		);
		$this->m_urlStates[$url] = $state;
		return $state['title_future'];
	}

	// 全処理終わるまで待つ
	// http://qiita.com/Hiraku/items/1c67b51040246efb4254
	public function waitFor(){
		// 処理内容が何も無ければ何もしない
		$cnt = 0;
		foreach($this->m_urlStates as $state){
			if($state['state'] == 0)$cnt++;
		}
		if($cnt == 0)return;
		
		//タイムアウト時間を決めておく
		$TIMEOUT = 8; //8秒

		/*
		 * 1) 準備
		 *  - curl_multiハンドラを用意
		 *  - 各リクエストに対応するcurlハンドラを用意
		 *    リクエスト分だけ必要
		 *    * レスポンスが必要な場合はRETURNTRANSFERオプションをtrueにしておくこと。
		 *  - 全てcurl_multiハンドラに追加
		 */
		$mh = curl_multi_init();
		foreach ($this->m_urlStates as $url => &$st) {
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => $TIMEOUT,
				CURLOPT_CONNECTTIMEOUT => $TIMEOUT,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS      => 4,
				CURLOPT_MAXCONNECTS    => 10,
			));
			curl_multi_add_handle($mh, $ch);
			//
			$st['ch'] = $ch;
			$st['state'] = 1;
		}
		// error_log_multi("==============m_urlStates = " . var_export($this->m_urlStates, true));

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
			error_log('リクエストが開始出来なかった');
			curl_multi_close($mh);
			return;
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
				error_log("Site title resolution: timeout.");
				break 2; // タイムアウトにつき抜ける
				continue 2; //ここではcontinueでリトライします。

			default: //どれかが成功 or 失敗した
				do {
					$stat = curl_multi_exec($mh, $running); //ステータスを更新
				} while ($stat === CURLM_CALL_MULTI_PERFORM);

				do if ($raised = curl_multi_info_read($mh, $remains)) {
					// 変化のあったcurlハンドラを取得する
					$info = curl_getinfo($raised['handle']);
					// error_log_multi("==============raised = " . var_export($raised, true));
					// error_log_multi("==============info = " . var_export($info, true));
					// echo "{$info['url']}: {$info['http_code']}\n";
					$response = curl_multi_getcontent($raised['handle']);

					if ($response === false) {
						//エラー。404などが返ってきている
						error_log("curl ERROR. URL:{$info['url']}");
					}
					else {
						//正常にレスポンス取得
						// echo $response, PHP_EOL;

						// state取得 -> $pstate
						unset($pstate);
						$pstate = null;
						foreach($this->m_urlStates as $url => &$state){
							if($state['ch'] === $raised['handle']){
								$pstate = &$state;
								// error_log("!!!!!!!OK!!!!!!!!!! " . $state['url']);
							}
						}
						if(!$pstate){
							error_log("state not found. URL:{$info['url']}");
							continue;
						}
						
						// title取得
						$title = html2title($response);
						if($title === false){
							error_log("html title not found. OriginalURL:{$pstate['url']} RedirectURL:{$info['url']}");
							$title = $pstate['url'];
						}
						$pstate['title'] = $title;
					}
					curl_multi_remove_handle($mh, $raised['handle']);
					curl_close($raised['handle']);
				} while ($remains);
			//select前に全ての処理が終わっていたりすると
			//複数の結果が入っていることがあるのでループが必要

		} while ($running);
		// echo 'finished', PHP_EOL;
		curl_multi_close($mh);
	}
	public function replaceFuture($html){
		// 候補をすべて置換
		foreach($this->m_urlStates as $url => $state){
			// 置換
			$html = str_replace($state['title_future'], htmlspecialchars($state['title']), $html);
			// ついでにDB保存
			global $g_db;
			$stmt = $g_db->prepare('INSERT INTO sites(url, title) VALUES(?, ?)');
			$stmt->execute(array($url, $state['title']));
		}
		return $html;
	}
}

function url2title($url){
	// DB接続、TABLE作成
	global $g_db;
	if(!isset($g_db)){
		$g_db = new PDO("sqlite:" . TMP_ROOT . '/sites.db', "", "");
		if($g_db){
			$ret = $g_db->exec(
				'CREATE TABLE sites(id INTEGER PRIMARY KEY AUTOINCREMENT, url VARCHAR(255), title VARCHAR(255))'
			);
			// echo "$ret\n";

			$ret = $g_db->exec(
				'CREATE UNIQUE INDEX url ON sites(url)'
			);
			// echo "$ret\n";
		}
	}

	// DB接続に失敗している場合は負荷が気になるので名前取得処理自体をやめる
	if(!$g_db){
		return $url; // URLのままで表示
	}

	// DBから取得
	$stmt = $g_db->prepare('SELECT id, url, title FROM sites WHERE url = ?');
	$stmt->execute(array($url));
	$record = $stmt->fetch(PDO::FETCH_ASSOC);
	if($record){
		$title = $record['title'];
		return htmlspecialchars($title);
	}

	// インターネットから取得するためのキューに入れる
	$title = SiteManager::instance()->pushUrl($url);
	return $title;
}

// 全処理が終わるまで待つ
function url2title_wait(){
	SiteManager::instance()->waitFor();
}

// 残りの結果を置換
function url2title_replace_others($body){
	return SiteManager::instance()->replaceFuture($body);
}

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
