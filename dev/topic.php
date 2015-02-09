<?php

// =============================================================================

function json_fix_cyr($json_str) {

	$cyr_chars = [
		'\u0430' => 'а', '\u0410' => 'А', 
		'\u0431' => 'б', '\u0411' => 'Б', 
		'\u0432' => 'в', '\u0412' => 'В', 
		'\u0433' => 'г', '\u0413' => 'Г', 
		'\u0434' => 'д', '\u0414' => 'Д', 
		'\u0435' => 'е', '\u0415' => 'Е', 
		'\u0451' => 'ё', '\u0401' => 'Ё', 
		'\u0436' => 'ж', '\u0416' => 'Ж', 
		'\u0437' => 'з', '\u0417' => 'З', 
		'\u0438' => 'и', '\u0418' => 'И', 
		'\u0439' => 'й', '\u0419' => 'Й', 
		'\u043a' => 'к', '\u041a' => 'К', 
		'\u043b' => 'л', '\u041b' => 'Л', 
		'\u043c' => 'м', '\u041c' => 'М', 
		'\u043d' => 'н', '\u041d' => 'Н', 
		'\u043e' => 'о', '\u041e' => 'О', 
		'\u043f' => 'п', '\u041f' => 'П', 
		'\u0440' => 'р', '\u0420' => 'Р', 
		'\u0441' => 'с', '\u0421' => 'С', 
		'\u0442' => 'т', '\u0422' => 'Т', 
		'\u0443' => 'у', '\u0423' => 'У', 
		'\u0444' => 'ф', '\u0424' => 'Ф', 
		'\u0445' => 'х', '\u0425' => 'Х', 
		'\u0446' => 'ц', '\u0426' => 'Ц', 
		'\u0447' => 'ч', '\u0427' => 'Ч', 
		'\u0448' => 'ш', '\u0428' => 'Ш', 
		'\u0449' => 'щ', '\u0429' => 'Щ', 
		'\u044a' => 'ъ', '\u042a' => 'Ъ', 
		'\u044b' => 'ы', '\u042b' => 'Ы', 
		'\u044c' => 'ь', '\u042c' => 'Ь', 
		'\u044d' => 'э', '\u042d' => 'Э', 
		'\u044e' => 'ю', '\u042e' => 'Ю', 
		'\u044f' => 'я', '\u042f' => 'Я'
	];

	foreach ($cyr_chars as $cyr_char_key => $cyr_char) {
		$json_str = str_replace($cyr_char_key, $cyr_char, $json_str); 
	}

	return $json_str;
}

// =============================================================================

function yapVideo($matches) {
	$a  = ["w", "D", "Y", "l", "V", "3", "g", "n", "o", "I", "X", "i", "v", "M", "2", "U", "N", "9", "5", "Z", "d", "6", "L", "y", "7", "="]; 
	$b  = ["x", "c", "Q", "p", "z", "b", "W", "R", "H", "G", "1", "4", "t", "T", "m", "0", "f", "e", "k", "u", "a", "s", "B", "8", "J", "r"];
	$id = $matches[1];
	
	$ch2 = curl_init();
	curl_setopt($ch2, CURLOPT_HEADER, false);
	curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch2, CURLOPT_URL, "http://www.yapfiles.ru/get_playlist/?v={$id}");
	$str = curl_exec($ch2);
	curl_close($ch2);
	
	$url = '';
	if (!empty($str)) {
		for ($i=0; $i<count($a); $i++) {
			$z = $a[$i];
			$x = $b[$i];
			$str = str_replace($z, "___", $str);
			$str = str_replace($x, $z, $str);
			$str = str_replace("___", $x, $str);
		}
		$data = json_decode(base64_decode($str), true);
		$url = $data['playlist'][0]['file'];
	}
	return '[&nbsp;<a href="'.$url.'">Yap&nbsp;Video</a>&nbsp;]';
}

// =============================================================================

error_reporting(0);
ob_start();

header('Content-type: application/json; charset=utf-8');

include __DIR__ . '/lib/simple_html_dom.php';

date_default_timezone_set('MSK');

$topicId = (int) filter_input(INPUT_GET, 'id',    FILTER_SANITIZE_NUMBER_INT);
$page    = (int) filter_input(INPUT_GET, 'page',  FILTER_SANITIZE_NUMBER_INT);

if ($page < 0) {
	$page = 0;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_INTERFACE, '83.141.42.234');
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$st = 25 * $page;
curl_setopt($ch, CURLOPT_URL, "http://www.yaplakal.com/forum0/st/{$st}/topic{$topicId}.html");
$html = curl_exec($ch);

$html = preg_replace('~(<!--SpoilerBegin.*?-->)</div>~', '\1', $html);
$html = preg_replace("~<div class='postcolor'>\s*<!--SpoilerEEnd-->~", '<!--SpoilerEEnd-->', $html);
$html = preg_replace('~(<!--QuoteBegin.*?-->)</div>~', '\1', $html);
$html = preg_replace("~<div class='postcolor'>\s*<!--QuoteEEnd-->~", '<!--QuoteEEnd-->', $html);
$html = preg_replace('~<!--emo&(.*?)-->.*?<!--endemo-->~', ' \1 ', $html);
$html = preg_replace("~<br /><br /><span class='edit'>.*?</span>~", '', $html);

$html = preg_replace("~<br\s*/?>~", '<br>', $html);

$dom = str_get_html($html);
$data = [];

if (FALSE !== $dom) {

	$rateDiv = $dom->find('div[rel=rating]', 0);
	if (NULL !== $rateDiv) {
		$rate = $rateDiv->find('div[title=Rank]', 0)->plaintext;
		$rateDiv->outertext = '';
	}

	$db = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=yap;user=yap;password=yap');

	$set = $db->prepare("INSERT INTO yap_image (hash, width, height) VALUES (decode(md5(?), 'hex'), ?, ?)");
	$get = $db->prepare("SELECT width, height FROM yap_image WHERE hash = decode(md5(?), 'hex') LIMIT 1");
	$upd = $db->prepare("UPDATE yap_topic SET preview_type = 'img', preview = ?, original = ? WHERE id = ?");

	foreach($dom->find('form table[id^=p_row_]') as $key => $element) {

		$usr_id  = $element->find('span.postdetails a', 0)->href;
		if (!empty($usr_id)) {
			preg_match('~member(\d+)\.~', $usr_id, $match1);
			$user_id = $match1[1];
		} else {
			$user_id = 0;
		}

		$id   = (int) substr($element->find('span[id^=pb_]', 0)->id, 3);
		$rate = ($rate === NULL) ? $element->find('div.post-rank span', 0)->innertext : $rate;
		$user = $element->find('span.normalname a', 0)->innertext;
		$user = strip_tags($user);
		if (empty($user)) { $user = $element->find('span.unreg', 0)->innertext; }
		$date = $element->find('div.desc a', 0)->innertext;
		$ava  = $element->find('span.postdetails a img', 0)->src;
		if (empty($ava)) { $ava  = $element->find('tr', 1)->find('td', 0)->find('img', 0)->src; }
		if (!empty($ava) && $ava[0] == '/') { $ava = "http://www.yaplakal.com{$ava}"; }
		$post = $element->find('tr.collapsebox div.postcolor', 0);

		$width    = 0;
		$height   = 0;
		$preview  = '';
		$original = '';

		$img = $post->find('div[id^=img_]', 0);
		if (NULL !== $img) {
			if (NULL !== $img->find('a', 0)->href) {
				$preview  = $img->find('img', 0)->src;
				$original = $img->find('a', 0)->href;
				if ('#' == $original) {
					$original = $preview;
				}
			} else {
				$preview  = $img->find('img', 0)->src;
				$original = $preview;
			}
			$img->outertext = '';
			
			$get->execute([$preview]);
			$result = $get->fetchAll(PDO::FETCH_ASSOC);
			if (!empty($result[0]['width'])) {
				$width  = $result[0]['width'];
				$height = $result[0]['height'];
			} else {
				list($width, $height) = @getimagesize($preview);
				if ($width > 0 && $height > 0) {
					$set->execute([$preview, $width, $height]);
					if ($key == 0 && $page == 0) {
						$upd->execute([$preview, $original, $topicId]);
					}
				} else {
					$width    = 0;
					$height   = 0;
					$preview  = '';
					$original = '';
				}
			}
		}

		$text = trim($post->innertext );
		$text = preg_replace('~<!--QuoteBegin.*?-->.*?<!--QuoteEBegin-->(<br>|\s)*~', '<blockquote>', $text);
		$text = preg_replace('~<!--QuoteEnd-->.*?<!--QuoteEEnd-->(<br>|\s)*~', '</blockquote>', $text);

		$text = preg_replace('~<!--QuoteBegin.*?-->.*?<!--QuoteEBegin-->(<br>|\s)*~', '<blockquote>', $text);
		$text = preg_replace('~<!--QuoteEnd-->.*?<!--QuoteEEnd-->(<br>|\s)*~', '</blockquote>', $text);

		$text = preg_replace('~<!--SpoilerBegin.*?-->.*?<!--SpoilerEBegin-->(<br>|\s)*~', '<blockquote>', $text);
		$text = preg_replace('~<!--SpoilerEnd-->.*?<!--SpoilerEEnd-->(<br>|\s)*~', '</blockquote>', $text);

		$text = preg_replace('~<!--modb-->.*?<!--mode-->~s', '', $text);

		// ---------------------------------------------------------------------

		// Конвертируем ссылки
		$text = preg_replace(
			'~<a href=["\'](http://www\.yaplakal\.com/go|/go|/click)/\?(.*?)["\'].*?>~e',
			'urldecode("<a href=\"$2\">")',
			$text
		);

		// YouTube
		$patter = [
			'~<!--Begin Video:https?://(www\.|m\.)?youtube\.com/watch(\?|\?.*?&amp;)v=(.*?)(&amp;.*?)?-->.*?<!--End Video-->~s',
			'~<!--Begin Video:https?://(youtu)\.(be)/(.*?)\s*-->.*?<!--End Video-->~s',
			'~(<center>)?<object.*?(youtube)\.com/v/(.*?)".*?</object>(</center>)?~s',
		];
		$text = preg_replace($patter, "[&nbsp;<a href=\"http://youtu.be/$3\">YouTube</a>&nbsp;]", $text);

		// YapVideo
		$pattern = [
			0 => '~<!--Begin Video.*?flashvars: {"st":"(.*?)".*?<!--End Video-->~s',
			1 => '~<!--Flash.*?flashvars: {"st":"(.*?)".*?<!--End Flash-->~s'
		];
		$text = preg_replace_callback($pattern, 'yapVideo', $text);
		
		// YapFiles h264 Video
		/*
		$text = preg_replace(
			'~<!--Begin Video:http://www\.yapfiles\.ru/(show|files)/\d+/(.*?)\.flv(.*?)\{"st":"(.*?)",(.*?)<!--End Video-->~s',
			'[&nbsp;<a href="http://www.yapfiles.ru/static/play.swf?st=$4">Yap&nbsp;Video</a>&nbsp;]', 
			$text
		);
		 */

		// Разное видео
		$patter = [
			0 => '~(<center>)?<object.*?rutube\.ru/(.*?)".*?</object>(</center>)?~s',
			1 => '~<!--Begin Video:https?://(video\.)?rutube\.ru\/(.*?)-->.*?<!--End Video-->~s',
			2 => '~<!--Begin Video:https?://(www\.)?clipiki\.ru/(.*?)-->.*?<!--End Video-->~s',
			3 => '~<!--Begin Video:https?://(www\.)?smotri\.com/(.*?)-->.*?<!--End Video-->~s',
			4 => '~<!--Begin Video:.*?<!--End Video-->~s'
		];
		$replacement = [
			0 => '[&nbsp;<a href="http://video.rutube.ru/$2">RuTube&nbsp;Video</a>&nbsp;]',
			1 => '[&nbsp;<a href="http://video.rutube.ru/$2">RuTube&nbsp;Video</a>&nbsp;]',
			2 => '[&nbsp;<a href="http://clipiki.ru/$2">Clipiki.ru&nbsp;Video</a>&nbsp;]',
			3 => '[&nbsp;<a href="http://smotri.com/$2">Smotri.com&nbsp;Video</a>&nbsp;]',
			4 => '[&nbsp;ExtVideo&nbsp;]'
		];
		$text = preg_replace($patter, $replacement, $text);

		// Flash
		$patter = [
			0 => '~<!--Flash (\d+)\+(\d+)\+(.*?)-->.*?<!--End Flash-->~s',
			1 => '~<!--dohtml//-->(.*?)<!--/dohtml//-->~',
			2 => '~<dohtml>(.*?)</dohtml>~',
			3 => '~(<center>)?<object.*?</object>(</center>)?~s'
		];
		$replacement = [
			0 => '[&nbsp;<a href="$3">Yap Flash</a> ]',
			1 => '[&nbsp;dohtml&nbsp;]',
			2 => '[&nbsp;dohtml&nbsp;]',
			3 => '[&nbsp;Flash&nbsp;]'
		];
		$text = preg_replace($patter, $replacement, $text);

		// ---------------------------------------------------------------------

		$text = preg_replace("~<a.*?><img src='(.*?\.gif)'.*?></a>~", '[&nbsp;<a href="$1">GIF</a>&nbsp;]', $text);
		$text = preg_replace("~<a.*?><img.*?src='(.*?\.png)'.*?></a>~", '[&nbsp;<a href="$1">PNG</a>&nbsp;]', $text);
		
		$text = preg_replace("~<img.*?src='(.*?\.gif)'.*?>~", '[&nbsp;<a href="$1">GIF</a>&nbsp;]', $text);
		$text = preg_replace("~<img.*?src='(.*?\.jpe?g)'.*?>~", '[&nbsp;<a href="$1">JPEG</a>&nbsp;]', $text);

		$text = strip_tags($text, '<br><blockquote><b><u><i><sup><sub><a>');
		$text = preg_replace('~<br>\s+<br>~', '<br><br>', $text);
		$text = preg_replace('~\s*(<br>\s*)+$~', '', $text);
		$text = preg_replace('~^\s*(<br>\s*)+~', '', $text);
		$text = preg_replace('~\s+~', ' ', $text);

		$text = trim($text);

		$data[] = [
			'id'         => (int) $id,
			'created_at' => (string) $date,
			'user_id'    => (int) $user_id,
			'user_name'  => (string) html_entity_decode(iconv('CP1251', 'UTF-8', $user), ENT_HTML5, 'UTF-8'),
			'avatar'     => (string) $ava,
			'rating'     => (int) $rate,
			'text'       => (string) iconv('CP1251', 'UTF-8', $text),
			'width'      => (int) $width,
			'height'     => (int) $height,
			'preview'    => (string) $preview,
			'original'   => (string) $original
		];

		$rate = NULL;
	}
}

curl_close($ch);

$json = [
	'meta' => null,
	'data' => $data
];

echo json_fix_cyr(json_encode($json));

//EOF