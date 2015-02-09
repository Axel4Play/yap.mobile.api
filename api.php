<!DOCTYPE html>
<html lang="ru-RU">
<head>
<meta charset="windows-1251">
<meta name="viewport" content="minimum-scale=1.0,initial-scale=1.0,maximum-scale=1.0,user-scalable=no,target-densitydpi=medium-dpi">
<meta name="format-detection" content="telephone=no">
<link rel="stylesheet" type="text/css" href="//api.m-yap.ru/s.css">
</head>
<body>
<?php

include 'simple_html_dom.php';
include 'strip_tags_smart.php';

$format = $_GET['format'];
$type   = $_GET['type'];
$id     = (int) $_GET['id'];
$page   = (int) $_GET['page'];

function declOfNum($number, $titles) {
    $cases = array (2, 0, 1, 1, 1, 2);
    return $number . ' ' . $titles[ ($number%100>4 && $number%100<20)? 2 : $cases[min($number%10, 5)] ];
}

// Лента
if ('l' === $type) {
	$domain = [
		'www.yaplakal.com',       //Главная
		'pics.yaplakal.com',      //Картинки
		'video.yaplakal.com',     //Видео
		'news.yaplakal.com',      //События
		'auto.yaplakal.com',      //Авто/Мото
		'animals.yaplakal.com',   //Зверьё
		'fotozhaba.yaplakal.com', //Фотожаба
		'flash.yaplakal.com',     //Флэш
		'art.yaplakal.com',       //Арт
		'inkubator.yaplakal.com'  //Инкубатор
	];

	$offset = ($page > 0) ? ($page - 1) * 50 : 0;
	$url = "http://{$domain[$id]}/st/{$offset}";
	$rawhtml = file_get_contents($url);
	$html = str_get_html($rawhtml);
	$data = [
		'pages' => 1,
		'items' => []
	];



	$rawpage = $html->find('table.lenta tr', -1)->find('td',0)->plaintext;
	if (NULL !== $rawpage) {
		preg_match('~\((\d+)\)~', $rawpage, $match);
		$data['pages'] = intval($match[1]);
	}

	foreach($html->find('table.lenta td[id^=topic_]') as $key => $element) {
		$data['items'][$key]['link'] = $element->find('a.subtitle', 0)->href;
		preg_match('~/forum(\d+)/topic(\d+)\.~', $data['items'][$key]['link'], $match);
		$data['items'][$key]['forumid'] = (int) $match[1];
		$data['items'][$key]['topicid'] = (int) $match[2];
		$data['items'][$key]['title'] = $element->find('a.subtitle', 0)->innertext;
		$data['items'][$key]['desc'] = $element->find('span', 0)->innertext;
		$data['items'][$key]['rate'] = $element->find('div.rating-short-value a', 0)->innertext;
		if (NULL !== $data['items'][$key]['rate']) {
			$data['items'][$key]['rate']  = (int) $data['items'][$key]['rate'];
		}
	}

	foreach($html->find('table.lenta td[id^=news_]') as $key => $element) {
		foreach ($element->find('div[id^=img_]') as $img) {
			$src = $img->find('img', 0)->src;
			//$img->outertext = '[IMG]';
			$img->outertext = '<img class=i src="' . $src . '">';
		}
		foreach ($element->find('img[class!=i]') as $img) {
			$img->outertext = '';
		}
		foreach ($element->find('a') as $link) {
			$link->outertext = $link->innertext;
		}
		$data['items'][$key]['text'] = $element->innertext;
		$data['items'][$key]['text'] = preg_replace('~\s*<br\s*/>\s*~', '<br>', $data['items'][$key]['text']);
		$data['items'][$key]['text'] = strip_tags_smart($data['items'][$key]['text'], ['br','img']);
		$data['items'][$key]['text'] = preg_replace(iconv('utf-8','cp1251','~<br><br>Читать\sдальше\.\.\.~'), '', $data['items'][$key]['text']);
		$data['items'][$key]['text'] = trim($data['items'][$key]['text']);
	}

	foreach($html->find('table.lenta td.newsbottom') as $key => $element) {
		$data['items'][$key]['user'] = $element->find('b.icon-user a', 0)->innertext;
		$data['items'][$key]['date'] = $element->find('b.icon-date', 0)->innertext;
		$data['items'][$key]['comm'] = $element->find('b.icon-comments span', 0)->innertext;
		if (NULL !== $data['items'][$key]['comm']) {
			$data['items'][$key]['comm']  = (int) str_replace(['(',')'], '', $data['items'][$key]['comm']);
		}
	}

	$page = ($page > 1) ? $page : 1;

	if ($page > 1) {
		$back = $page - 1;
		echo <<<EOF
<a class=p href="/h/l/{$id}/{$back}">Page {$back} / {$data['pages']}</a>
EOF;
	}

	foreach ($data['items'] as $key => $item) {
		if (NULL !== $item['rate']) {

			$desc = '';
			if (!empty($item['desc'])) {
				$desc = "<div class=desc>{$item['desc']}</div>";
			}

			$comm = declOfNum($item['comm'], ['комментарий', 'комментария', 'комментариев']);
			$comm = iconv('utf-8','cp1251',$comm);

			$title = str_replace("&#39;", "\'", $item['title']);

			echo <<<EOF
<div class=item onClick="yap.showTopic('{$item['topicid']}','1','{$title}')">
<div><span class=rate>{$item['rate']}<span><span class=user> &bull; {$item['user']}</span></div>
<div class=title>{$item['title']}</div>{$desc}
<div class=comm>{$comm}</div>
<div class=short>{$item['text']}</div>
</div>
EOF;
		}
	}

	if ($page < $data['pages']) {
		$next = $page + 1;
		echo <<<EOF
<a class=p href="/h/l/{$id}/{$next}">Page {$next} / {$data['pages']}</a>
EOF;
	}
}

if ('f' === $type) {

	$forums = [
        0,  //Главная
        2,  //Картинки
        28, //Видео
        1,  //События
        11, //Авто/Мото
        13, //Зверьё
        18, //Двигатель Торговли
        43, //Фотопутешествия
        7,  //Анекдоты и приколы
        27, //Фотожаба
        6,  //Креативы
        40, //ЯП-издат
        8,  //Флэш
        41, //Поэзия
        38, //Весёлая рифма
        9,  //Золото
        36, //Клубничка
        26, //Коллекция
        16, //ЯП-Обзор
        14, //Беседы на Завалинке
        32, //ЭВМъ
        44, //Барахолка
        33, //Про Это
        45, //Цех ЯП-творчества
        29, //Кинематографъ
        31, //Крутятся Диски
        42, //Игры
        30, //Кулинария
        24, //Физкультура и спорт
        37, //Поздравления
        3,  //Инкубатор
        5,  //Работа сайта,
		23  //Склад баянов

	];

	$offset = ($page > 0) ? ($page - 1) * 30 : 0;
	$url = "http://www.yaplakal.com/forum{$forums[$id]}/st/{$offset}/100/Z-A/last_post";
	$rawhtml = file_get_contents($url);

	$html = str_get_html($rawhtml);
	$data = [
		'pages' => 1,
		'items' => []
	];

	$rawpage = $html->find('div[id=content-inner]', 0)->find('table', 0)->find('td', 0)->plaintext;
	if (NULL !== $rawpage) {
		preg_match('~\((\d+)\)~', $rawpage, $match);
		$data['pages'] = intval($match[1]);
	}

	foreach($html->find('form table tr') as $key => $element) {
		$row = $element->find('td', 2);
		if (NULL !== $row) {
			$item = $row->find('a.subtitle', 0);
			if (NULL !== $item) {
				$data['items'][$key]['title'] = $item->innertext;
				$data['items'][$key]['desc'] = $row->find('div.desc', 0)->innertext;
				$data['items'][$key]['link'] = $item->href;
				preg_match('~/forum(\d+)/topic(\d+)\.~', $data['items'][$key]['link'], $match);
				$data['items'][$key]['forumid'] = (int) $match[1];
				$data['items'][$key]['topicid'] = (int) $match[2];
				$data['items'][$key]['user'] = $element->find('td', 3)->plaintext;
				$data['items'][$key]['comm'] = (int) $element->find('td', 4)->innertext;
				$data['items'][$key]['view'] = (int) $element->find('td', 5)->innertext;
				$data['items'][$key]['rate'] = (int) $element->find('td', 6)->find('div', 0)->innertext;
			}
		}
	}

	$page = ($page > 1) ? $page : 1;

	if ($page > 1) {
		$back = $page - 1;
		echo <<<EOF
<a class=p href="/h/f/{$id}/{$back}">Page {$back} / {$data['pages']}</a>
EOF;
	}

	foreach ($data['items'] as $key => $item) {

		$desc = '';
		if (!empty($item['desc'])) {
			$desc = "<div class=desc>{$item['desc']}</div>";
		}

		$comm = declOfNum($item['comm'], ['комментарий', 'комментария', 'комментариев']);
		$comm = iconv('utf-8','cp1251',$comm);

		$view = declOfNum($item['view'], ['просмотр', 'просмотра', 'просмотров']);
		$view = iconv('utf-8','cp1251',$view);

		$title = str_replace("&#39;", "\'", $item['title']);

		echo <<<EOF
<div class=item onClick="yap.showTopic('{$item['topicid']}','1','{$title}')">
<div><span class=rate>{$item['rate']}<span><span class=user> &bull; {$item['user']}</span></div>
<div class=title>{$item['title']}</div>{$desc}
<div class=comm>{$comm} &bull; {$view}</div>
</div>
EOF;
	}

	if ($page < $data['pages']) {
		$next = $page + 1;
		echo <<<EOF
<a class=p href="/h/f/{$id}/{$next}">Page {$next} / {$data['pages']}</a>
EOF;
	}

}

if ('t' === $type) {
	$offset = ($page > 0) ? ($page - 1) * 25 : 0;
	$url = "http://www.yaplakal.com/forum0/st/{$offset}/topic{$id}.html";
	$rawhtml = file_get_contents($url);

	$rawhtml = preg_replace('~(<!--QuoteBegin.*?-->)</div>~', '\1', $rawhtml);
	$rawhtml = preg_replace("~<div class='postcolor'>\s*<!--QuoteEEnd-->~", '<!--QuoteEEnd-->', $rawhtml);

	$html = str_get_html($rawhtml);
	$data = [];

	$data['title'] = $html->find('h1.subpage a.subtitle', 0)->innertext;
	$data['desc'] = substr($html->find('h1.subpage span', 0)->innertext, 2);
	$data['view'] = intval(substr($html->find('table.activeuserstrip td', 1)->innertext, 16));

	$data['pages'] = 1;
	$data['rate'] = NULL;

	$rawpage = $html->find('table.tableborder table.row3', 0)->find('tr', 1)->find('td', 0)->plaintext;
	if (NULL !== $rawpage) {
		preg_match('~\((\d+)\)~', $rawpage, $match);
		$data['pages'] = intval($match[1]);
	}

	foreach($html->find('form table[id^=p_row_]') as $key => $element) {

		$post = $element->find('tr.collapsebox div.postcolor', 0);

		if (0 == $key) {
			$rateDiv = $post->find('div[rel=rating]', 0);
			if (NULL !== $rateDiv) {
				$data['rate'] = (int) $rateDiv->find('div[title=Rank]', 0)->plaintext;
				$rateDiv->outertext = '';
			}
		}

		foreach ($post->find('div[id^=img_]') as $image) {
			if (NULL !== $image->find('a', 0)->href) {
				$src = $image->find('a', 0)->href;
			} else {
				$src = $image->find('img', 0)->src;
			}
			$image->outertext = '<img onClick="yap.showImage(\'' . $src . '\')" class=i src="' . $src . '">';
		}

		foreach ($post->find('a') as $link) {
			$link->outertext = $link->innertext;
		}

		foreach ($post->find('img') as $img) {
			$src = $img->src;
			$img->outertext = '<img onClick="yap.showImage(\'' . $src . '\')" class=i src="' . $src . '">';
		}

		$data['items'][$key]['user'] = $element->find('span.normalname a', 0)->innertext;
		$data['items'][$key]['date'] = $element->find('div.desc a', 0)->innertext;
		$data['items'][$key]['rate'] = $element->find('div.post-rank span', 0)->innertext;
		$data['items'][$key]['ava']  = $element->find('span.postdetails a img', 0)->src;

		$data['items'][$key]['text'] = $post->innertext;
		$data['items'][$key]['text'] = preg_replace("~<!--emo&(.*?)-->.*?<!--endemo-->~", ' \1 ', $data['items'][$key]['text']);
		$data['items'][$key]['text'] = preg_replace("~<br /><br /><span class='edit'>.*?</span>~", '', $data['items'][$key]['text']);
		$data['items'][$key]['text'] = preg_replace('~\s*<br\s*/>\s*~', '<br>', $data['items'][$key]['text']);
		$data['items'][$key]['text'] = strip_tags_smart($data['items'][$key]['text'], ['br','img','a']);
		//$data['items'][$key]['text'] = trim(htmlspecialchars( $data['items'][$key]['text'], ENT_COMPAT, cp1251) );
		$data['items'][$key]['text'] = trim($data['items'][$key]['text']);
	}

	$desc = '';
	if (!empty($item['desc'])) {
		$desc = "<div class=desc>{$data['desc']}</div>";
	}

	$view = declOfNum($data['view'], ['просмотр', 'просмотра', 'просмотров']);
	$view = iconv('utf-8', 'cp1251', $view);

	$pages = declOfNum($data['pages'], ['страница', 'страницы', 'страниц']);
	$pages = iconv('utf-8', 'cp1251', $pages);

	$page = ($page > 1) ? $page : 1;

	if ($page > 1) {
		$back = $page - 1;
		echo <<<EOF
<a class=p href="/h/t/{$id}/{$back}">Page {$back} / {$data['pages']}</a>
EOF;
	}

	echo <<<EOF
<div class=item>
<div class=title>{$data['title']}</div>{$desc}
<div class=comm>{$data['rate']} &bull; {$view} &bull; {$pages}</div>
</div>
EOF;

	foreach ($data['items'] as $key => $item) {

		$rate = '';
		if (NULL !== $item['rate']) {
			$rate = " &bull; {$item['rate']}";
		}

		echo <<<EOF
<div class=msg>
<div class=hdr style="background-image:url('{$item['ava']}')">
<div class=usr>{$item['user']}</div>
<div class=date>{$item['date']}{$rate}</div>
</div>
<div class="txt">{$item['text']}</div>
</div>
EOF;
	}

	if ($page < $data['pages']) {
		$next = $page + 1;
		echo <<<EOF
<a class=p href="/h/t/{$id}/{$next}">Page {$next} / {$data['pages']}</a>
EOF;
	}
}

?>
</body>
</html>