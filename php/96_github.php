<?php

/*
Usage:
  $github = new GitHub();
  $github->signup();
 */
class GitHub{
	public function GitHub(){
		$this->m_stream = stream_context_create(array('http' => array(
			'method' => 'GET',
			'header' => "User-Agent: {GitHubSettings::USER_AGENT}", // これ付けないと404 not foundになってしまう。。
		)));
	}

	// これを外から呼ぶ
	// @return String GitHubユーザ名
	public function signup(){
		$client_id = GitHubSettings::CLIENT_ID;
		$redirect_url = GitHubSettings::REDIRECT_URL;

		//get request , either code from github, or login request
		if($_SERVER['REQUEST_METHOD'] != 'GET')return;

		//authorised at github
		if(!isset($_GET['code'])){
			// まず、githubのoauthにリダイレクト
			// scope=user を付けるとメールアドレスとかも取得できる (アプリ権限があれば)
			$url = "https://github.com/login/oauth/authorize?client_id=$client_id&redirect_uri=$redirect_url"; //&scope=user";
			header("Location: $url");
			exit(0);
		}
		else{
			// 認証が済めばここに戻ってくる
			$code = $_GET['code'];

			//perform post request now
			$post = http_build_query(array(
				'client_id' => $client_id ,
				'redirect_uri' => $redirect_url ,
				'client_secret' => GitHubSettings::CLIENT_SECRET,
				'code' => $code ,
			));

			// -- -- githubサーバからaccess_token取得 -- -- //
			// https post
			$context = stream_context_create(array("http" => array(
				"method" => "POST",
				"header" => "Content-Type: application/x-www-form-urlencoded\r\n" .
					"Content-Length: ". strlen($post) . "\r\n".
					"Accept: application/json\r\n" .
					"User-Agent: {GitHubSettings::USER_AGENT}",
				"content" => $post,

			)));
			$json_data = @file_get_contents("https://github.com/login/oauth/access_token", false, $context);
			if($json_data === false){ // おそらくネットワークエラー
				return '';
			}
			//var_dump($json_data);

			// json parse
			// 例: {"access_token":"xxxxx","token_type":"bearer","scope":""}
			$r = json_decode($json_data, true);
			if(!isset($r['access_token'])){
				die("access_token not found");
			}
			$access_token = $r['access_token'];

			// -- -- access_tokenを使ってユーザ情報を取得 -- -- //
			// https get
			$url = "https://api.github.com/user?access_token=$access_token";
			$data =	@file_get_contents($url, false, $this->m_stream);
			if($data === false){ // おそらくネットワークエラー
				return '';
			}

			// json parse
			/*
			例:
			{
			"login":"kobake",
			"avatar_url":"https://avatars.githubusercontent.com/u/2929454?v=2",

			"id":2929454,
			"type":"User",
			"gravatar_id":"684c15ae167ad4f6c8cd29326260e72c",
			"url":"https://api.github.com/users/kobake",
			"html_url":"https://github.com/kobake",
			"followers_url":"https://api.github.com/users/kobake/followers", "following_url":"https://api.github.com/users/kobake/following{/other_user}",
			"gists_url":"https://api.github.com/users/kobake/gists{/gist_id}", "starred_url":"https://api.github.com/users/kobake/starred{/owner}{/repo}",
			"subscriptions_url":"https://api.github.com/users/kobake/subscriptions", "organizations_url":"https://api.github.com/users/kobake/orgs",
			"repos_url":"https://api.github.com/users/kobake/repos", "events_url":"https://api.github.com/users/kobake/events{/privacy}",
			"received_events_url":"https://api.github.com/users/kobake/received_events",
			"site_admin":false, "name":"", "company":"",
			"blog":"http://blog.clock-up.jp/", "location":"", "email":"", "hireable":false, "bio":null, "public_repos":44, "public_gists":17, "followers":9, "following":16,
			"created_at":"2012-11-30T09:14:17Z", "updated_at":"2014-08-06T08:48:33Z"
			}
			*/
			//var_dump($data);
			$user_data = json_decode($data , true);
			$username = $user_data['login'];

			// メールアドレス等も取得する場合
			// ※これの実行にはscope=userが必要
			/*
			$emails =  file_get_contents("https://api.github.com/user/emails?access_token=$access_token", false, $this->m_stream);
			$emails = json_decode($emails , true);
			$email = $emails[0];
			*/

			// -- -- 結果 -- -- //
			return $username;
			/*
			$signup_data = array(
				'username' => $username ,
				// 'email' => $email ,
				'source' => 'github' ,
			);
			$this->dump($signup_data);
			*/
		}
	}

	// 確認用
	public function dump($signup_data){
		var_dump($signup_data);
	}
}
