<?php
/*
 *
 * 
 * /api/html/ - Образец
 * 
 * /api/h/l/0 - Лента: Главная, страница 1
 * /api/h/l/0/12 - Лента: Главная, страница 12
 * /api/h/l/pics/ - Лента: Картинки, страница 1
 * /api/h/f/28/ - Форму: Картинки, страница 1
 * /api/h/f/28/66/ - Форму: Картинки, страница 66
 * /api/h/t/571317/ - Тема: Тема на форуме, страница 1
 * /api/h/t/571317/123/ - Тема: Тема на форуме, страница 123
 * 
 * /api/x/
 * /api/j/
 * 
 */


include 'simple_html_dom.php';
include 'strip_tags_smart.php';

if (1 == $_GET['a']) {
	// Главная
	
	$page = 50 * intval($_GET['p']);
	
	$url = "http://www.yaplakal.com/st/{$page}";
	
	$rawhtml = file_get_contents($url);
	$preg = iconv('utf-8','cp1251','~<br><br><a.*?><b>Читать дальше\.\.\.</b></a>~');
	$rawhtml = preg_replace($preg, '', $rawhtml);
	$rawhtml = preg_replace('~\s*<br\s*/>\s*~', '<br>', $rawhtml);
	
	$html = str_get_html($rawhtml);
	
	$data = [];
	
	foreach($html->find('table.lenta td[id^=topic_]') as $key => $element) {
		$data[$key]['link'] = $element->find('a.subtitle', 0)->href;
		$data[$key]['title'] = $element->find('a.subtitle', 0)->innertext;
		$data[$key]['desc'] = $element->find('span', 0)->innertext;
		$data[$key]['rate'] = $element->find('div.rating-short-value a', 0)->innertext;
	}
	
	foreach($html->find('table.lenta td[id^=news_]') as $key => $element) {
		foreach ($element->find('div[id^=img_]') as $img) {
			$href = $img->find('img', 0)->src;
			$img->outertext = '[ IMG: ' . $href . ' ]';
		}
		foreach ($element->find('a') as $link) {
			//$href = $link->href;
			//$link->outertext = '[ LINK: ' . $href . ' ]';
			$link->outertext = $link->innertext;
		}
		$data[$key]['text'] = trim($element->innertext);
	}

	foreach($html->find('table.lenta td.newsbottom') as $key => $element) {
		$data[$key]['user'] = $element->find('b.icon-user a', 0)->innertext;
		$data[$key]['date'] = $element->find('b.icon-date', 0)->innertext;
		$data[$key]['comments'] = $element->find('b.icon-comments span', 0)->innertext;
	}
	
}

/*
echo '<pre>';
var_dump($data);
echo '</pre>';

echo '<hr>';
*/

foreach ($data as $item) {
	if (NULL !== $item['rate']) {
		
		echo '<div style="border:1px dotted #ccc;width:200px;"><b>';
		echo $item['title'];
		echo '</b></div>';
		echo '<div style="border:1px dotted #ddd;width:200px;">';
		//echo htmlspecialchars(strip_tags_smart($item['text'], ['<br>']),ENT_COMPAT,cp1251);
		echo strip_tags_smart($item['text'], ['<br>']);
		//echo $item['text'];
		echo '</div>';
	}
}

exit();








$url = 'http://www.yaplakal.com/forum1/topic570742.html';



$rawhtml = file_get_contents($url);

$rawhtml = preg_replace('~(<!--QuoteBegin.*?-->)</div>~', '\1', $rawhtml);
$rawhtml = preg_replace("~<div class='postcolor'>\s*<!--QuoteEEnd-->~", '<!--QuoteEEnd-->', $rawhtml);
$rawhtml = preg_replace("~<!--emo&(.*?)-->.*?<!--endemo-->~", ' \1 ', $rawhtml);
$rawhtml = preg_replace('~<div style="clear: both;"></div>~', '', $rawhtml);
$rawhtml = preg_replace("~<br /><br /><span class='edit'>.*?</span>~", '', $rawhtml);
$rawhtml = preg_replace('~\s+~', ' ', $rawhtml);



	


$html = str_get_html($rawhtml);

foreach($html->find('form table[id^=p_row_]') as $key => $element) {
	
	$post = $element->find('tr.collapsebox div.postcolor', 0);
	
	if (0 == $key) {
		$rateDiv = $post->find('div[rel=rating]', 0);
		if (NULL !== $rateDiv) {
			$rate = $rateDiv->find('div[title=Rank]', 0)->plaintext;
			$rateDiv->outertext = '';
		}
		//var_dump($rate);
	}
	
	foreach ($post->find('div[id^=img_]') as $img) {
		if (NULL !== $img->find('a', 0)->href) {
			$href = $img->find('a', 0)->href;
		} else {
			$href = $img->find('img', 0)->src;
		}
		$img->outertext = '[ IMG: '.$href.' ]';
	}
	
	foreach ($post->find('img') as $img) {
		$href = $img->src;
		$img->outertext = '[ IMG: '.$href.' ]';
	}
	
	$data[] = [
		'user' => $element->find('span.normalname a', 0)->innertext,
		'date' => $element->find('div.desc a', 0)->innertext,
		'rate' => $element->find('div.post-rank span', 0)->innertext,
		'ava'  => $element->find('span.postdetails a img', 0)->src,
		'html' => trim(htmlspecialchars($post->innertext,ENT_COMPAT,cp1251)),
		//'text' => trim($post->plaintext),
	];
	
	$item2[] = $post->innertext;
}

/*
echo '<pre>';
echo htmlspecialchars($rawhtml,ENT_COMPAT,cp1251);
echo '</pre>';
echo '<hr>';
 * 
 */

foreach ($item2 as $id => $row) {
	$x = $id + 1;
	echo '<hr/>';
	echo '<b>' . $x . '</b> : ', $row;
	echo '<hr/>';
	echo '<b>' . $x . '</b> : ', htmlspecialchars($row,ENT_COMPAT,cp1251);
}
echo '<hr><hr>';
echo '<pre>';
var_dump($data);
echo '</pre>';



//$html = file_get_html('http://www.yaplakal.com/forum2/st/25/topic570912.html');

/*
foreach($html->find('td.newshead') as $element) {
	$item['rate'][] = $element->find('div.rating-short-value a', 0)->plaintext;
}

foreach($html->find('td.news-content') as $element) {
	$item['content'][] = trim($element->plaintext);
}

foreach($html->find('td.newsbottom') as $element) {
	$item['author'][] = $element->find('b.icon-user a', 0)->plaintext;
}
*/

/*
foreach($html->find('table.tableborder form table') as $z1) {
	$item[] = htmlspecialchars(trim($z1->plaintext),ENT_COMPAT,cp1251);
}

echo '<pre>';
var_dump($item);
echo '</pre>';
*/
	