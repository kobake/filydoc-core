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
	public function signup(){
		$client_id = GitHubSettings::CLIENT_ID;
		$redirect_url = GitHubSettings::REDIRECT_URL;

		//get request , either code from github, or login request
		if($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			//authorised at github
			if(isset($_GET['code']))
			{
				$code = $_GET['code'];

				//perform post request now
				$post = http_build_query(array(
					'client_id' => $client_id ,
					'redirect_uri' => $redirect_url ,
					'client_secret' => GitHubSettings::CLIENT_SECRET,
					'code' => $code ,
				));

				$context = stream_context_create(array("http" => array(
					"method" => "POST",
					"header" => "Content-Type: application/x-www-form-urlencoded\r\n" .
						"Content-Length: ". strlen($post) . "\r\n".
						"Accept: application/json\r\n" .
						'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
					"content" => $post,

				)));

				$json_data = file_get_contents("https://github.com/login/oauth/access_token", false, $context);
				var_dump($json_data);

				$r = json_decode($json_data , true);

				if(!isset($r['access_token'])){
					die("access_token not found");
				}
				$access_token = $r['access_token'];

				$url = "https://api.github.com/user?access_token=$access_token";

				$data =	 file_get_contents($url, false, $this->m_stream);

				//echo $data;
				$user_data	= json_decode($data , true);
				$username = $user_data['login'];


				// これの実行にはscope=userが必要
				/*
				$emails =  file_get_contents("https://api.github.com/user/emails?access_token=$access_token", false, $this->m_stream);
				$emails = json_decode($emails , true);
				$email = $emails[0];
				*/

				$signup_data = array(
					'username' => $username ,
					// 'email' => $email ,
					'source' => 'github' ,
				);

				$this->dump($signup_data);
			}
			else
			{
				// scope=user を付けるとメールアドレスとかも取得できる (アプリ権限があれば)
				$url = "https://github.com/login/oauth/authorize?client_id=$client_id&redirect_uri=$redirect_url"; //&scope=user";
				header("Location: $url");
			}
		}
	}

	// 確認用
	public function dump($signup_data){
		var_dump($signup_data);
	}
}
