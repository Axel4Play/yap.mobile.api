<?php

$pages = 5;

$lentas = [
	1001 => 'www',
	1002 => 'pics',
	1003 => 'video',
	1004 => 'news',
	1005 => 'auto',
	1006 => 'animals',
	1007 => 'fotozhaba',
	1008 => 'flash',
	1009 => 'art',
	1010 => 'inkubator'
];

$db = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=yap;user=yap;password=yap');
$del = $db->prepare('DELETE FROM yap_lenta WHERE forum_id = ?');
$ins = $db->prepare('INSERT INTO yap_lenta (forum_id, sort, topic_id) VALUES (?, ?, ?)');

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

foreach ($lentas as $id => $lenta) {
	$data = [];
	for ($page = 1; $page <= $pages; $page++) {
		$st = 50 * ($page - 1);
		curl_setopt($ch, CURLOPT_URL, "http://{$lenta}.yaplakal.com/st/{$st}");
		$html = curl_exec($ch);
		//$errn = curl_errno($ch);
		//$info = curl_getinfo($ch);
		preg_match_all('~<b class="icon-comments"><a href="http://www.yaplakal.com/forum(\d+)/topic(\d+).html">~', $html, $out);
		$data = array_merge($data, $out[2]);
	}
	if (count($data) == 50 * $pages) {
		$del->execute([(int) $id]);
		foreach ($data as $sort => $topic) {
			$ins->execute([(int) $id, (int) $sort, (int) $topic]);
		}
		echo "{$lenta} - OK\r\n";
	} else {
		echo "{$lenta} - Error - ";
		echo count($data);
		echo "\r\n";
	}
}

curl_close($ch);

//EOF