<?php

ob_start();

header('Content-type: application/json; charset=utf-8');

date_default_timezone_set('MSK');

$login    = 'ЯПМобайл'; //(string) filter_input(INPUT_GET, 'login');
$password = 'mobile56'; //(string) filter_input(INPUT_GET, 'password');
$topic    =  547804;    //(int)    filter_input(INPUT_GET, 'topic', FILTER_SANITIZE_NUMBER_INT);
$text     = 'ping';     //(string) filter_input(INPUT_GET, 'text');

if (!empty($text)) {

	$time = mktime();
	$cookie = __DIR__.'/cookies/'.md5($login.$password.$time);
	
	$post = [
		'CookieDate' =>	1,
		'PassWord'   => iconv('UTF-8','CP1251', $password),
		'Secure'     =>	1,
		'UserName'   => iconv('UTF-8','CP1251', $login),
		'referer'    => '',
		'submit'     => iconv('UTF-8','CP1251', 'Вход'),
		'user_key'   => md5('')
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, "http://www.yaplakal.com/act/Login/CODE/01/");
	curl_setopt($ch, CURLOPT_POST, true);
	//curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie);
	//curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	$html = curl_exec($ch);
	curl_close($ch);
	
	$str = iconv('UTF-8', 'CP1251', 'вы вошли как');
	preg_match("~{$str}~", $html, $match);

	if (count($match) > 0) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, "http://www.yaplakal.com/forum3/topic{$topic}.html");
		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$html = curl_exec($ch);
		curl_close($ch);

		preg_match("~name='f' value='(\d+)'~", $html, $match1);
		preg_match("~name='auth_key' value='(.*?)'~", $html, $match2);

		var_dump($match1, $match2);
		
		exit();
		
		if (count($match2) > 0) {

			$forum  = (int) $match1[1];
			$auth_key = (string) $match2[1];

			$post = [
				'act'           => 'Post',
				'CODE'          => '03',
				'f'             => $forum,
				't'	            => $topic,
				'st'            => 0,
				'enableemo'     => 'yes',
				'enablesig'     => 'yes',
				'auth_key'      => $auth_key,
				'Post'          => iconv('UTF-8', 'CP1251', "{$text}\r\n[color=#CCCCCC]--\r\nОтправлено через ЯП.Мобайл[/color]"),
				'MAX_FILE_SIZE' => 512000,
				'FILE_UPLOAD'   => '',
				'enabletag'     => 1
			];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, "http://www.yaplakal.com");
			curl_setopt($ch, CURLOPT_POST, true);
			//curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
			$html = curl_exec($ch);
			curl_close($ch);

			//var_dump($html);

			$status = 1;
			$error  = 'Сообщение наверное отправлено';

		} else {
			$status = 0;
			$error  = 'Не найден код авторизации';
		}

	} else {
		$status = 0;
		$error  = 'Ошибка авторизации';
	}

	if (file_exists($cookie)) {
		unlink($cookie);
	}
	
} else {
	$status = 0;
	$error  = 'Пустое сообщение';
}

echo '{"status":' . $status . ',"error":"' . $error . '"}';

//EOF