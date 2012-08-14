<?php

function getKeywordURL($keyword) {
	$out = '';
	if (preg_match('/[#\/]/', $keyword)) {
		$out = '?mode=key&param=';
	} else {
		$out = 'k/';
	}
	$out .= rawurlencode(mb_convert_encoding($keyword, 'UTF-8', 'auto'));
	return $out;
}

function pictureLink($imgurl, $alt = '', $linkto, $isAvailableThumbnail = 0) {
	global $mobile, $opt_showpict;
	$outType = $opt_showpict;

	if ($isAvailableThumbnail == '1' && (($opt_showpict == '0' && $mobile) || $opt_showpict == '1')) {
		/* Hatena */
		$imgurl = substr($imgurl, 0, -4) . '_120.jpg';
	}

	if ($opt_showpict == '0' && $mobile) {
		$outType = ($isAvailableThumbnail) ? 1 : 2;
	}
	if ($opt_showpict == '1') {
		if ($isAvailableThumbnail) { $outType = 1; }
		else {
			$outType = ($mobile) ? 2 : 0;
		}
	}
	switch ($outType) {
		case '0':
		case '1':
			$out  = '<a href="'.$linkto.'"><img src="'.$imgurl.'" alt="'.$alt.'"></a>';
			break;
		case '2':
			if ($mobile) {
				$out = "<a href=\"{$linkto}\">(画像を開く)</a>";
			} else {
				$out = "<span class=\"picopen\" onclick=\"showImage('{$linkto}', '{$imgurl}', '{$alt}', this)\"><noscript><a href=\"{$linkto}\"></noscript>(画像を開く)<noscript></a></noscript></span>";
			}
			break;
	}
	return $out;
}

function fotolifeSintax($matches) {
	global $mobile;
	$alt     = $matches[0];
	$id      = $matches[1];
	$initial = substr($matches[1], 0, 1);
	$date    = $matches[2];
	$time    = $matches[3];
	$type    = (isset($matches[4]) && $matches[4]) ? $matches[4] : '';
	$mode    = (isset($matches[5]) && $matches[5]) ? $matches[5] : '';
	$ext     = 'jpg';
	if ($type == 'g') { $ext = 'gif'; }
	else if ($type == 'p') { $ext = 'png'; }
	$link = 'http://f.hatena.ne.jp/'.$id.'/'.$date.$time;
	$img  = 'http://cdn-ak.f.st-hatena.com/images/fotolife/'.$initial.'/'.$id.'/'.$date.'/'.$date.$time.'.'.$ext;

	$out = pictureLink($img, $alt, $link, 1);

	if ($type == 'f' && $mode == ':movie' && !$mobile) {
		// Movie(Mobile以外のmovie)
		$out = <<<EOM
<object data="http://f.hatena.ne.jp/tools/flvplayer_s.swf" type="application/x-shockwave-flash" height="276" width="320">
<param name="movie" value="http://f.hatena.ne.jp/tools/flvplayer_s.swf">
<param name="FlashVars" value="fotoid={$date}{$time}&amp;user={$id}">
<param name="wmode" value="transparent">
<a href="http://f.hatena.ne.jp/{$id}/{$date}{$time}"><img src="http://cdn-ak.f.st-hatena.com/images/fotolife/{$initial}/{$id}/{$date}/{$date}{$time}.jpg" alt="f:id:{$id}:{$date}{$time}f:movie"></a>
</object>
EOM;
	}
	return $out;
}

function qbsintax($matches) {
	$id = $matches[1];
	$initial = substr($id,0,2);
	$time = time();
	// 読み込み
	$url = 'http://www.st-hatena.com/users/'.$initial.'/'.$id.'/happie.gif';
	$image = imagecreatefromGIF($url);
	// 頭
	$tmp = imagecreatetruecolor(75, 55);
	imagecopy($tmp, $image, 0, 0, 0, 0, 75, 55);
	ImageGif($tmp, "qbtmp/$time.gif");
	$image1 = 'data://image/gif;base64,'.base64_encode(file_get_contents("qbtmp/$time.gif"));
	// 胴体
	$tmp = imagecreatetruecolor(75, 71);
	imagecopy($tmp, $image, 0, 0, 0, 55, 75, 71);
	ImageGif($tmp, "qbtmp/$time.gif");
	$image2 = 'data://image/gif;base64,'.base64_encode(file_get_contents("qbtmp/$time.gif"));
	// 画像は消す
	unlink("qbtmp/$time.gif");
	$out = <<<EOM
<img src="$image1">&lt;もう何も恐くない<br /><br /><img src="$image2">
EOM;
	return $out;
}

function amazonLink($matches) {
	global $baseurl, $mobile, $access_key_id, $secret_access_key;
	$asin = $matches[2];
	$len  = strlen($asin);
	if ($len == 13) {
		$type = intval(substr($asin, 0, 3));
		/*if ($type == 491) {
			Magazines : むりぽ
			return $matches[0];
		}
		else*/ if ($type == 978 || $type == 491) {
			/* Books */
			$asinarray = str_split(substr($asin, 3));
			$k = 0;
			foreach($asinarray as $i=>$v) {
				if ($i == 9){break;}
				$k += $v * (10 - $i);
			}
			$cdigit = 11 - ($k % 11);
			$asin = substr($asin, 3, 9) . $cdigit;
		}
	}
	$xml  = getAmazonItemXMLbyASIN($asin, $access_key_id, $secret_access_key);
	if ($xml->Items->Request->Errors) {return "".$matches[0];}
	$item = $xml->Items->Item;
	$link  = 'http://www.amazon.co.jp/exec/obidos/ASIN/' . $asin . '/akkienisshi-22/ref=nosim/';
	$title = $item->ItemAttributes->Title;
	$image = $item->SmallImage->URL;
	$out = '<a href='.$baseurl.'k/asin%3A'.$asin.'><img src="'.$image.'" style="width:16px;height:16px;">'.$title.'</a>';
	return $out;
}

function parseHaikuText($keyword, $text, $mobile, $spamchecksw = 1) {
	global $baseurl, $script, $dbg, $opt_showpict;

	$out = "";
	$picLinkCount = 4;
	$videoLinkCount = 4;

	$text  = htmlspecialchars($text);
	$text  = str_replace('&amp;', '&', $text);

	$len = strlen($text);
	if (1638 < $len && $spamchecksw) {
		/* 819Moji Over Text */
		$text = substr($text, 0, 1638);
		$text .= '<br><span style="color:#777;font-style:italic;">(省略されました。閲覧するには(Reply)を開いて下さい。本文サイズ'.$len.'Bytes)</span>';
	}
	$lines = substr_count($text, "\n") + 1;
	if (15 < $lines && $spamchecksw) {
		preg_match_all('/\n/', $text, $matches, PREG_OFFSET_CAPTURE);
		$text = substr($text, 0, $matches[0][14][1]);
		$text .= '<br><span style="color:#777;font-style:italic;">(省略されました。閲覧するには(Reply)を開いて下さい。行数'.$lines.'行)</span>';
	}

	// URLの処理
	$shift = 0;
	$urlpattern = '/\[?(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/';
	preg_match_all($urlpattern, $text, $matches, PREG_OFFSET_CAPTURE);
	foreach ($matches[0] as $match) {
		$offset = $match[1] + $shift;
		$match = $match[0];
			// $offset = 見つかったURLのある位置
			// $shift  = ループ中の直前の置換でズレた分の補正
			// $match  = 見つかったURL

		// Skip URL Sintax
		if ($match[0] == '[') { continue; }

		/* 画像 */
		if (0 < $picLinkCount) {
			// フォト蔵
			if (preg_match('/(http:\/\/photozou\.jp\/photo\/show\/)([0-9]+)\/([0-9]+)/', $match, $imgmatch)) {
				$xml    = simplexml_load_file('http://api.photozou.jp/rest/photo_info?photo_id='.$imgmatch[3]);
				$photo  = $xml->info->photo;
				$imgurl = ($mobile || $opt_showpict == '1')  ? $photo->thumbnail_image_url : $photo->image_url;
				$text = substr_replace(
					$text,
					pictureLink($imgurl, $photo->photo_title, $photo->url, 2),
					$offset, strlen($match));
				$shift += strlen($replace) - strlen($match);
				$picLinkCount--;
				continue;
			}

			// fotolife
			$pattern = '/(http:\/\/(img\.f\.hatena\.ne\.jp|cdn\.f\.st-hatena\.com|cdn-ak\.f\.st-hatena\.com)\/images\/fotolife\/)([-_a-zA-Z0-9]+)\/([-_a-zA-Z0-9]+)\/([0-9]+)\/([0-9]+)\.(jpg|gif|png)/';
			if (preg_match($pattern, $match)) {
				$replace = pictureLink($match, $match, $match, 1);
				$text = substr_replace($text, $replace, $offset, strlen($match));
				$shift += strlen($replace) - strlen($match);
				$picLinkCount--;
				continue;
			}

			// yfrog
			if (preg_match('/(http:\/\/yfrog\.com\/)([0-9A-Za-z]+)/', $match, $imgmatch)) {
				$imgurl = "http://yfrog.com/{$imgmatch[2]}";
				if ($mobile || $opt_showpict == '1') {
					$replace = pictureLink($imgurl.":small", "yflog:{$imgmatch[2]}", $imgurl, 2);
				} else {
					$replace = pictureLink($imgurl.":iphone", "yflog:{$imgmatch[2]}", $imgurl, 2);
				}
				$text = substr_replace($text, $replace, $offset, strlen($match));
				$shift += strlen($replace) - strlen($match);
				$picLinkCount--;
				continue;
			}

			// Twitpic
			if (preg_match('/(http:\/\/twitpic\.com\/)([0-9A-Za-z]+)/', $match, $imgmatch)) {
				$replace = pictureLink("http://twitpic.com/show/thumb/{$imgmatch[2]}", "twitpic:{$imgmatch[2]}", $match, 2);
				$text = substr_replace($text, $replace, $offset, strlen($match));
				$shift += strlen($replace) - strlen($match);
				$picLinkCount--;
				continue;
			}

			// 普通の画像URL
			if (preg_match('/\.(jpg|jpeg|gif|png|JPG|JPEG|GIF|PNG)$/', $match)) {
				$replace = pictureLink($match, $match, $match, 0);
				$text = substr_replace($text, $replace, $offset, strlen($match));
				$shift += strlen($replace) - strlen($match);
				$picLinkCount--;
				continue;
			}
		}

		/* 動画 */
		if (0 < $videoLinkCount) {
			$pattern = array(); $replace = array();
			// ニコ動
			$pattern[] = '/http:\/\/(www\.)?(nicovideo\.jp\/watch|nico\.ms)\/(sm|nm)([0-9]+)/';
			$replace[] = '<div><iframe width="300" height="185" src="http://ext.nicovideo.jp/thumb/\\3\\4" scrolling="no" style="border:solid 1px #CCC;" frameborder="0"><a href="\\0">\\0</a></iframe></div>';
			// YouTube
			$pattern[] = '/http:\/\/(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([-_0-9a-zA-Z]+)/';
			$replace[] = '<div class="yt"><object width="300" height="225"><param name="movie" value="http://www.youtube.com/v/\\3"><param name="wmode" value="transparent"><embed src="http://www.youtube.com/v/\\3" type="application/x-shockwave-flash" wmode="transparent" width="300" height="225"></object></div>';

			$result = preg_replace($pattern, $replace, $match);
			if ($result != $match) {
				$text = substr_replace($text, $result, $offset, strlen($match));
				$shift += strlen($result) - strlen($match);
				$videoLinkCount--;
				continue;
			}
		}

		// それ以外
		$replace = '<a href="'.$match.'">'.$match.'</a>';
		$text = substr_replace($text, $replace, $offset, strlen($match));
		$shift += strlen($replace) - strlen($match);
	}

	// URL Sintax
	$sintaxes = explode('[', $text);
	if ($sintaxes[0] != $text) {
		foreach ($sintaxes as $sintax) {
			$i = strpos($sintax, ']');
			if ($i === FALSE) {
				continue;		/* これは記法ではない。 */
			}
			$sintax = substr($sintax, 0, $i);
			$sintaxOut = preg_replace('/(https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)\:title=(.+)/', '<a href="\\1">\\2</a>', $sintax);
			$text = ($sintax != $sintaxOut) ? str_replace('['.$sintax.']', $sintaxOut, $text) : $text;
		}
	}

	// Keyword Sintax
	$sintaxes = explode('[[', $text);
	if ($sintaxes[0] != $text) {
		foreach ($sintaxes as $sintax) {
			$i = strpos($sintax, ']]');
			if ($i === FALSE) {
				continue;		/* これは記法ではない。 */
			}
			$sintax = substr($sintax, 0, $i);
			$newsintax = str_replace("\n", '<br>', $sintax);
			$sintaxOut = '<a href="'.getKeywordURL($newsintax).'">'.$newsintax.'</a>';
			$text = ($sintax != $sintaxOut) ? str_replace('[['.$sintax.']]', $sintaxOut, $text) : $text;
		}
	}

	// Sintax including id call
	$shift = 0;
	$pattern = '/(f:)?id:([-_a-zA-Z0-9]+):?([0-9]{14})?(j|g|p|f)?(:image)?(:movie)?/';
	preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
	foreach ($matches[0] as $match) {
		$offset = $match[1] + $shift;
		$match = $match[0];
		// Fotolife Sintax
		if (0 < $picLinkCount) {
			$result = preg_replace_callback(
				'/f:id:([-_a-zA-Z0-9]+):([0-9]{8})([0-9]{6})(j|g|p|f)?(:image|:movie)?/i',
				"fotolifeSintax", $match
			);
			if ($result != $match) {
				$text = substr_replace($text, $result, $offset, strlen($match));
				$shift += strlen($result) - strlen($match);
				$picLinkCount--;
				continue;
			}
		}
		// ID Call
		$pattern = '/id\:([-_0-9a-zA-Z]{2})([-_0-9a-zA-Z]+)/';
		$replace = '<a href="'.$baseurl.'u/\\1\\2"><img src="http://www.st-hatena.com/users/\\1/\\1\\2/profile_s.gif">\\0</a>';
		$result = preg_replace($pattern, $replace, $match);
		$text = substr_replace($text, $result, $offset, strlen($match));
		$shift += strlen($result) - strlen($match);
	}

	// BOKU TO KEIYAKU SHITE JISSOU SHIYOU YO!
	$text = preg_replace_callback('/id:([-_a-zA-Z0-9]+):qb/', "qbsintax", $text);

	// ASIN or ISBN Sintax
	$text = preg_replace_callback('/(ASIN|asin):([a-zA-Z0-9]+)/',	"amazonLink", $text);
	$text = preg_replace_callback('/(ISBN|isbn):([X0-9]+)/',		"amazonLink", $text);

	// Other Sintaxes
	$pattern = array(
		'/idea:([0-9]+)/',
		'/map\:([.0-9]+)\:([.0-9]+)/',
		'/\n/'
	);
	$replace = array(
		'<a href="http://i.hatena.ne.jp/idea/\\1">\\0</a>',
		'<a href="http://maps.google.com/maps?q=\\1,\\2"><img src="http://maps.google.com/maps/api/staticmap?maptype=mobile&markers=\\1%2C\\2&sensor=false&size=140x140&zoom=13" alt="map"></a><br>',
		'<br>'
	);
	$text = preg_replace($pattern, $replace, $text);

	// 現在の端末用の HTML_Emoji オブジェクトを作成
//	$emoji = HTML_Emoji::getInstance();
	// PC で表示する際に用いる画像ファイルの URL を指定
//	$emoji->setImageUrl('images/');
	// 現在の端末で表示するのに適した形にデータを変換
	//$out = $emoji->filter($text, 'output');
//	$text = $emoji->convertCarrier($text);

//	$text = str_replace("\n", '<br>', $text);

	// AA Sintax(これは改行を<br>に変換した後でやる)
	preg_match_all('/\&gt\;\|aa\|\<br\>/', $text, $matches, PREG_OFFSET_CAPTURE);
	foreach ($matches[0] as $match) {
		$offset = $match[1];
		$match = $match[0];
		$i = strpos($text, '||&lt;', $offset);
		if ($i === FALSE) {
			continue;		/* これは記法ではない。 */
		}
		$text = substr_replace($text, '<div class="aa">', $offset, 12);
		$text = substr_replace($text, '</div>', $i, 10);
		// Shift +4 -4 == 0
	}

	return $text;
}

?>