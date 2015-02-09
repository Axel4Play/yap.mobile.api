<?php

error_reporting(0);
date_default_timezone_set('Europe/Moscow');
ob_start();

header('Content-type: application/json; charset=utf-8');

$login    = (string) filter_input(INPUT_GET, 'login');
$password = (string) filter_input(INPUT_GET, 'password');
$topic    = (int)    filter_input(INPUT_GET, 'topic', FILTER_SANITIZE_NUMBER_INT);
$post     = (int)    filter_input(INPUT_GET, 'post',  FILTER_SANITIZE_NUMBER_INT);
$rank     = (int)    filter_input(INPUT_GET, 'rank',  FILTER_SANITIZE_NUMBER_INT);

$rank = ($rank == 0) ? 1 : -1;

$time   = time();
$cookie = __DIR__.'/cookies/'.md5($login.$password.$time);

$postdata = [
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
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
$html = curl_exec($ch);
curl_close($ch);

$str = iconv('UTF-8', 'CP1251', 'вы вошли как');
preg_match("~{$str}~", $html, $match);

if (count($match) > 0) {
	$url = "http://www.yaplakal.com/index_.php?act=ST&t={$topic}&p={$post}&CODE=vote_post&n=1&rank={$rank}&rand=0.15626414972138392";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	$html = curl_exec($ch);
	curl_close($ch);

	if (strlen($html) > 0) {
		$html = iconv('CP1251', 'UTF-8', $html);
		$json = json_decode($html, true);
		if ($json['status'] == 1) {
			$status = 1;
			$error  = 'ОК';
		} else {
			$status = 0;
			$error  = (!empty($json['error'])) ? $json['error'] : "Ошибка";
		}
	} else {
		$status = 0;
		$error  = 'Неизвестная ошибка';
	}

} else {
	$status = 0;
	$error  = 'Ошибка авторизации';
}

echo '{"status":' . $status . ',"error":"' . $error . '"}';

if (file_exists($cookie)) {
	unlink($cookie);
}

//EOF