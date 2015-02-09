<?php

ob_start();

header('Content-type: application/json; charset=utf-8');

date_default_timezone_set('MSK');

$login    = (string) filter_input(INPUT_GET, 'login');
$password = (string) filter_input(INPUT_GET, 'password');

$time     = mktime();
$cookie   = __DIR__.'/cookies/'.md5($login.$password.$time);

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
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);

$html = curl_exec($ch);
curl_close($ch);

$str = iconv('UTF-8', 'CP1251', 'вы вошли как');

preg_match("~{$str}~", $html, $match);

if (count($match) > 0) {
	$status = 1;
} else {
	$status = 0;
}

echo json_encode([
	'status' => $status
]);

if (file_exists($cookie)) {
	unlink($cookie);
}

//EOF