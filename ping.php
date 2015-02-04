<?php
/*
 * コマンドラインから呼ぶ用
 */
if(php_sapi_name() != 'cli'){
	print("This script is allowed to run only from command line.\n");
	exit(0);
}
$res = sendPing2();
print("res = $res\n");

// 参考：
//   http://piyopiyocs.blog115.fc2.com/blog-entry-497.html
//   http://www.softel.co.jp/blogs/tech/archives/2418
//   http://seo.siyo.org/ping/seo8449/
// Usage:
//   $res = sendPing("blogsearch.google.co.jp", "/ping/RPC2", "clock-up-memo", "http://memo.clock-up.jp");
function sendPing() {
	//更新Pingの送信先
	$server = 'http://blogsearch.google.co.jp/ping/RPC2';

	//weblogUpdates.ping のXML-RPCのリクエストを作る
	$content = xmlrpc_encode_request(
		'weblogUpdates.ping',
		// 4つ目の引数の"カテゴリ"は省略してよい
		// array('clock-up-memo', 'http://memo.clock-up.jp/', 'http://memo.clock-up.jp/linux'),
		array('clock-up-memo', 'http://memo.clock-up.jp/', 'http://memo.clock-up.jp/linux/crontab'),
		array('encoding' => 'UTF-8')
	);

	//HTTPコンテキスト [http://www.php.net/manual/ja/context.http.php] 参照
	$options = array('http'=>array(
		'method' => 'POST',
		'header' => 'Content-type: text/xml' . "\r\n"
			. 'Content-length: ' . strlen($content),
		'content' => $content
	));
	$context = stream_context_create($options);

	//リクエスト送信
	$response = file_get_contents($server, false, $context);
	return $response;
}

function sendPing2(){
	$title = htmlspecialchars('clock-up-memo');
	$url = 'http://memo.clock-up.jp/';
	// http://blogsearch.google.co.jp/ping/RPC2
	$host = 'blogsearch.google.co.jp';
	$path = '/ping/RPC2';

    //---------------
    // 送付用XML
    //---------------
    $xml =<<<PING
<?xml version="1.0"?>
<methodCall>
<methodName>weblogUpdates.ping</methodName>
<params>
    <param>
        <value>$title</value>
    </param>
    <param>
        <value>$url</value>
    </param>
</params>
</methodCall>
PING;

    //---------------
    // POST内容
    //---------------
    $xmlLen = strlen($xml);
    $req = <<<REQ
POST $path HTTP/1.0
Host: $host
Content-Type: text/xml
Content-Length: $xmlLen

$xml
REQ;

    //---------------
    // 送信
    //---------------
    $s = @fsockopen($host, 80, $errNo, $errStr, 3);

    $res = "";
    if($s){
	    fputs($s, $req);
	    while(!feof($s)) {$res .= fread($s, 1024);}
    }

    // Ping送信先からの戻り内容を返す
    return $res;
}
