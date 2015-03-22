<?php

include dirname(__DIR__) . '/lib/simple_html_dom.php';

date_default_timezone_set('MSK');

$pages = 5;

$forums = [
	2,	// Картинки
	28,	// Видео
	1,	// События
	11,	// Авто/Мото
	13,	// Зверьё
	18,	// Двигатель Торговли
	43,	// Фотопутешествия
	7,	// Анекдоты и приколы
	27,	// Фотожаба
	6,	// Креативы
	40,	// ЯП-издат
	8,	// Флэш
	41,	// Поэзия
	38,	// Весёлая рифма
	9,	// Золото
	36,	// Клубничка
///	4,	// Уголок падонка
	26,	// Коллекция
//	16, // ЯП.Обзор
//	14, // Беседы на завалинке
	32,	// ЭВМъ
///	25, // ЯП Тусовка
//	44,	// Барахлка
	33,	// Про Это
	45,	// Цех ЯП-творчества
	29,	// Кинематографъ
	31,	// Крутятся Диски
	42,	// Игры
	30,	// Кулинария
	24,	// Физкультура и спорт
//	37,	// Подравления
	3,	// Инкубатор
	5,	// Работа сайта
///	17,	// Вторсырье
//	23, // Склад баянов
///	35,	// Утиль
];

$forums2 = [
	16, // ЯП.Обзор
	14, // Беседы на завалинке
	44,	// Барахлка
	37,	// Подравления
	23, // Склад баянов
];

$db = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=yap;user=yap;password=yap');
$ins = $db->prepare('INSERT INTO yap_topic (id, title, description, created_at, user_id, user_name, forum_id, rating, messages_count, last_message_time, update) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
$upd = $db->prepare('UPDATE yap_topic SET forum_id = ?, rating = ? WHERE id = ?');
$run = $db->prepare('UPDATE yap_topic SET update = 1, title = ?, description = ?, messages_count = ?, last_message_time = ? WHERE id = ? AND last_message_time <> ?');

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

// Разделы с рейтингом
foreach ($forums as $forum) {
	for ($page = 1; $page <= $pages; $page++) {
		$st = 30 * ($page - 1);
		curl_setopt($ch, CURLOPT_URL, "http://www.yaplakal.com/forum{$forum}/st/{$st}/100/Z-A/last_post");
		$html = curl_exec($ch);
		//$errn = curl_errno($ch);
		//$info = curl_getinfo($ch);
		$dom = str_get_html($html);
if (!$dom) {break;}
		$cnt = 0;
		foreach($dom->find('form table tr') as $key => $element) {
			$topic = [
				'id'                => (int) 0,
				'title'             => (string) '',
				'description'       => (string) '',
				'created_at'        => (int) 0,
				'user_id'           => (int) 0,
				'user_name'         => (string) '',
				'forum_id'          => (int) 0,
				'rating'            => (int) 0,
				'messages_count'    => (int) 0,
				'last_message_time' => (int) 0
			];
			$row = $element->find('td', 2);
			if (NULL !== $row) {
				$item = $row->find('a.subtitle', 0);
				if (NULL !== $item) {
					preg_match('~/forum(\d+)/topic(\d+)\.~', $item->href, $match);
					preg_match('~member(\d+)\.~', $element->find('td', 3)->find('a', 0)->href, $match2);
					preg_match('~(\d{1,2})\.(\d{1,2})\.(\d\d\d\d)\s-\s(\d{1,2}):(\d{1,2})~', $item->title, $match3);
					preg_match('~(\d{1,2})\.(\d{1,2})\.(\d\d\d\d)\s-\s(\d{1,2}):(\d{1,2})~', $element->find('td', 7)->find('span.desc', 0)->plaintext, $match4);
					
					$topic['id']                = (int) $match[2];
					$topic['title']             = (string) $item->innertext;
					$topic['description']       = (string) $row->find('div.desc', 0)->innertext;
					$topic['created_at']        = (int) mktime ($match3[4], $match3[5], 0, $match3[2], $match3[1], $match3[3]);
					//$topic['created_at2']       = (string) "{$match3[3]}-{$match3[2]}-{$match3[1]} {$match3[4]}:{$match3[5]}:00";
					$topic['user_id']           = (int) $match2[1];
					$topic['user_name']         = (string) $element->find('td', 3)->plaintext;
					$topic['forum_id']          = (int) $match[1];
					//$topic['view_count']        = (int) $element->find('td', 5)->innertext;
					$topic['rating']            = (int) $element->find('td', 6)->find('div', 0)->innertext;
					$topic['messages_count']    = (int) $element->find('td', 4)->innertext;
					$topic['last_message_time'] = (int) mktime ($match4[4], $match4[5], 0, $match4[2], $match4[1], $match4[3]);
					//$topic['last_message_time2'] = (string) "{$match4[3]}-{$match4[2]}-{$match4[1]} {$match4[4]}:{$match4[5]}:00";
					
					$ins->execute([
						$topic['id'],
						html_entity_decode($topic['title'], ENT_QUOTES, 'UTF-8'),
						html_entity_decode($topic['description'], ENT_QUOTES, 'UTF-8'),
						$topic['created_at'],
						$topic['user_id'],
						html_entity_decode($topic['user_name'], ENT_QUOTES, 'UTF-8'),
						$topic['forum_id'],
						$topic['rating'],
						$topic['messages_count'],
						$topic['last_message_time']
					]);					
					$upd->execute([
						$topic['forum_id'],
						$topic['rating'],
						$topic['id']
					]);
					$run->execute([
						html_entity_decode($topic['title'], ENT_QUOTES, 'UTF-8'),
						html_entity_decode($topic['description'], ENT_QUOTES, 'UTF-8'),
						$topic['messages_count'],
						$topic['last_message_time'],
						$topic['id'],
						$topic['last_message_time']
					]);
					$cnt += 1;
				}
			}
		}
		echo "{$forum} - {$page} - {$cnt}\r\n";
	}
}

// Разделы без рейтинга
foreach ($forums2 as $forum) {
	for ($page = 1; $page <= $pages; $page++) {
		$st = 30 * ($page - 1);
		curl_setopt($ch, CURLOPT_URL, "http://www.yaplakal.com/forum{$forum}/st/{$st}/100/Z-A/last_post");
		$html = curl_exec($ch);
		$errn = curl_errno($ch);
		$info = curl_getinfo($ch);
		$dom = str_get_html($html);
		$cnt = 0;
		foreach($dom->find('form table tr') as $key => $element) {
			$topic = [
				'id'                => (int) 0,
				'title'             => (string) '',
				'description'       => (string) '',
				'created_at'        => (int) 0,
				'user_id'           => (int) 0,
				'user_name'         => (string) '',
				'forum_id'          => (int) 0,
				'rating'            => (int) 0,
				'messages_count'    => (int) 0,
				'last_message_time' => (int) 0
			];
			$row = $element->find('td', 2);
			if (NULL !== $row) {
				$item = $row->find('a.subtitle', 0);
				if (NULL !== $item) {
					preg_match('~/forum(\d+)/topic(\d+)\.~', $item->href, $match);
					preg_match('~member(\d+)\.~', $element->find('td', 3)->find('a', 0)->href, $match2);
					preg_match('~(\d{1,2})\.(\d{1,2})\.(\d\d\d\d)\s-\s(\d{1,2}):(\d{1,2})~', $item->title, $match3);
					preg_match('~(\d{1,2})\.(\d{1,2})\.(\d\d\d\d)\s-\s(\d{1,2}):(\d{1,2})~', $element->find('td', 6)->find('span.desc', 0)->plaintext, $match4);
					
					$topic['id']                = (int) $match[2];
					$topic['title']             = (string) $item->innertext;
					$topic['description']       = (string) $row->find('div.desc', 0)->innertext;
					$topic['created_at']        = (int) mktime ($match3[4], $match3[5], 0, $match3[2], $match3[1], $match3[3]);
					//$topic['created_at2']       = (string) "{$match3[3]}-{$match3[2]}-{$match3[1]} {$match3[4]}:{$match3[5]}:00";
					$topic['user_id']           = (int) $match2[1];
					$topic['user_name']         = (string) $element->find('td', 3)->plaintext;
					$topic['forum_id']          = (int) $match[1];
					//$topic['view_count']        = (int) $element->find('td', 5)->innertext;
					$topic['messages_count']    = (int) $element->find('td', 4)->innertext;
					$topic['last_message_time'] = (int) mktime ($match4[4], $match4[5], 0, $match4[2], $match4[1], $match4[3]);
					//$topic['last_message_time2'] = (string) "{$match4[3]}-{$match4[2]}-{$match4[1]} {$match4[4]}:{$match4[5]}:00";
					
					$ins->execute([
						$topic['id'],
						html_entity_decode($topic['title'], ENT_QUOTES, 'UTF-8'),
						html_entity_decode($topic['description'], ENT_QUOTES, 'UTF-8'),
						$topic['created_at'],
						$topic['user_id'],
						html_entity_decode($topic['user_name'], ENT_QUOTES, 'UTF-8'),
						$topic['forum_id'],
						$topic['rating'],
						$topic['messages_count'],
						$topic['last_message_time']
					]);					
					$upd->execute([
						$topic['forum_id'],
						$topic['rating'],
						$topic['id']
					]);
					$run->execute([
						html_entity_decode($topic['title'], ENT_QUOTES, 'UTF-8'),
						html_entity_decode($topic['description'], ENT_QUOTES, 'UTF-8'),
						$topic['messages_count'],
						$topic['last_message_time'],
						$topic['id'],
						$topic['last_message_time']
					]);
					$cnt += 1;
				}
			}
		}
		echo "{$forum} - {$page} - {$cnt}\r\n";
	}
}

curl_close($ch);

//EOF
