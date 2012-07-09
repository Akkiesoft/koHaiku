<?php

	if (isset($_COOKIE["koHaiku"])) {
		$cookie = $_COOKIE["koHaiku"];
		list($username,$password) = explode(",", $_COOKIE['koHaiku']);
		$login = 1;
	} else {
		$login = 0;
	}

	require_once 'init.php';
	require_once 'func.php';

	$mode = '';
	$sata = '';
	$page = 1;
	$rtinfo    = '';
	$keyword   = '';
	$pagenavi  = 0;
	$haikuform = 0;

	if (isset($_POST['t8639'])) {
		/* Post to Haiku! */
		$opt = '';
		if ($_POST['sata'] != "") {
			$keyword = htmlspecialchars($_POST['sata']);
		} else if ($_POST['keyword'] != "") {
			$keyword = htmlspecialchars($_POST['keyword']);
		} else {
			$keyword = "id:".urlencode($username);
		}
		$text = (isset($_POST['sas'])) ? $_POST['sas'] : '';
		$file = (isset($_FILE['file'])) ? $_FILE['file'] : '';
		$camdata = (isset($_POST['camdata'])) ? $_POST['camdata'] : '';
if ($camdata) {
	$rawcamdata = base64_decode(substr($camdata, 22));
	if ($rawcamdata === FALSE) {die("Oh");}
	$hndl = imagecreatefromstring($rawcamdata);
	if ($hndl) {
		$camfilename = 'qbtmp/'.time().'.jpg';
		imagejpeg($hndl, $camfilename, 90);
	}
	$file = array(
		type=>'image/jpeg',
		tmp_name=>$camfilename
	);
}
		$opt = getKeywordURL($keyword);
		if ($text != '' || $file != '') {
			if (isset($_POST['rtid'])) {
				// reply 
				postEntry($keyword, $text, $file, $_POST['rtid']);
				$opt = "e/".urlencode($_POST['rtid']);
			} else {
				// normal
				postEntry($keyword, $text, $file);
			}
		}
if ($camdata) {
	unlink($camfilename);
}
		header('Location:'.$script.$opt);
	}

	if (isset($_POST['del_x']) || isset($_POST['del'])) {
		/* Delete Entry */
		$backTo = '';
		if (isset($_GET['mode']) && isset($_GET['param'])) {
			$mode = $_GET['mode'];
			$modeparam = $_GET['param'];
			if ($mode == 'user')		{ $backTo = 'u/'.$modeparam; }
			else if ($mode == 'key')	{ $backTo = getKeywordURL($modeparam); }
		}
		deleteEntry($_POST['eid']);
		header('Location:'.$script.$backTo);
		exit;
	}

	/* fillin */
	if (isset($_GET['fillbody'])) {
		$fillBody = htmlspecialchars($_GET['fillbody']);
	} else {
		$fillBody = '';
	}

	/* get Parameter */
	if (isset($_GET['mode'])) {
		$mode      = htmlspecialchars($_GET['mode']);
		$modeparam = htmlspecialchars($_GET['param']);
		if (preg_match('/\/$/', $modeparam)) { $modeparam = substr($modeparam, 0, -1); }
	}

	if ($mode == 'entry') {
		/* a Entry page */
		$entry = getEntry($modeparam);
		if (substr($entry, 0, 1) == '<') {
			$entry = preg_replace('/[\x00-\x09\x0b\x0c\x0e-\x1f]/', ' ', $entry);
			$xml = simplexml_load_string($entry);
			$skipflag = 0;
			if (mb_strpos($ngkey, $xml->keyword) !== FALSE) { $skipflag++; }
			if (mb_strpos($ngid, $xml->user->screen_name) !== FALSE) { $skipflag++; }
			if (!$skipflag) {
				$haikuform = 1;
				$out = parseEntry($xml, "pub", 'nospamchk');
				$rtinfo = '<input type="hidden" name="sata" value="'. $xml->keyword .'" /><input type="hidden" name="rtid" value="'. $modeparam .'" />';
			}
		} else {
			$out = "エントリー取得エラー。";
		}
	}
	else if ($mode == 'star') { 
		/* Star Information page. */
		$out = getStarData($modeparam);
	}
	else if ($mode == 'delete') {
		$entry = preg_replace('/[\x00-\x09\x0b\x0c\x0e-\x1f]/', ' ', $entry);
		$entry = getEntry($modeparam);
		if (substr($entry, 0, 1) == '<') {
			$xml = getEntry($modeparam);
			$out = parseEntry(simplexml_load_string($xml), "usr", 'delete');
			$out = 
				'<p>次のエントリを削除します。良ければOKボタンを押して下さい。</p>' . $out . '<form action="' .
				$script . '" method="post"><input type="hidden" name="eid" value="' . $modeparam .
				'" /><input type="submit" name="del" value="OK" /></form>';
		} else {
			$out = "エントリー取得エラー。";
		}
	}
	else if ($mode == 'user' && $modeparam == '') {
		if ($mobile == 1) {
			if ($login) {
				$out =  '<hr><h2>Following Keywords</h2>' . getKeywordList('fav', $username);
			} else {
				header('Location:'.$script.'login.php');
			}
		} else {
			die('ユーザ名が指定されていません。<a href="'.$script.'">もどる</a>');
		}
	}
	else if ($mode == 'key' && $modeparam == '') {
		if ($mobile == 1) {
			$out =  '<hr><h2>Hot Keywords</h2>' . getKeywordList('hot');
		} else {
			die('キーワードが指定されていません。<a href="'.$script.'">もどる</a>');
		}
	}
	else {
		/* Entries view */
		$req = new HTTP_Request();
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$color     = 'usr';		// Default(Pink)
		$pagenavi  = 1;
		$haikuform = 1;
		$amazblock = '';
		$showProfile = 0;

		if ($mode == 'user') {
			/* User Entries */
			$showProfile = "id:".$modeparam;
			$req->setURL('http://h.hatena.ne.jp/api/statuses/user_timeline/'.$modeparam.'.xml?body_formats=haiku');
		}
		else if ($mode == 'following') {
			/* Following Entries */
			$showProfile = "id:".$modeparam;
			if (!$modeparam) { die('ユーザ名が指定されていません。<a href="'.$script.'">もどる</a>'); }
			$req->setURL('http://h.hatena.ne.jp/api/statuses/friends_timeline/'.$modeparam.'.xml?body_formats=haiku');
		}
		else if ($mode == 'key') {
			/* Keyword */
			$showProfile = $modeparam;
			$keyurl = rawurlencode($modeparam);
			$req->setURL('http://h.hatena.ne.jp/api/statuses/keyword_timeline/'.$keyurl.'.xml?body_formats=haiku');
			$sata = $modeparam;
			$color = 'key';
		} else {
			/* Public Timeline */
			$req->setURL('http://h.hatena.ne.jp/api/statuses/public_timeline.xml?body_formats=haiku');
			$color = 'pub';
		}
		if (isset($_GET['page']) && $_GET['page']) {
			$page = htmlspecialchars($_GET['page']);
			$req->addQueryString('page', $page);
		}
		$res = $req->sendRequest();
		if(PEAR::isError($res)) {
			print $res->getMessage();
		}
		$ret = $req->getResponseCode();
		if ($ret == 200) {
			$out = parseEntries($req->getResponseBody(), $color, $showProfile);
		}
		else if ($ret == 500 && $mode == 'key') {
			/*新規キーワード  */
		}
		else {
			die('<!DOCTYPE html><html lang="ja"><head><meta charset="utf-8"></head><body>XML取得エラー！: '.$req->getResponseCode()."</body></html>");
		}
	}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Expires" content="0"><meta http-equiv="Content-Style-Type" content="text/css"><base href="<?php print $script; ?>"><title>koHaiku</title><?php print $mobilehead; ?>
<style type="text/css"><?php printCSS($mobile); ?></style>
</head>
<body>
<?php
printKoHaikuHeader();

if ($login) {
	if ($haikuform) {
		$multiform = ($isAndroid) ? ' enctype="multipart/form-data"' : '';
	}
?>
<form action="./" method="POST" id="haikuForm" name="haikuForm"<?php print $multiform; ?>>
<?php	if ($rtinfo == "") { ?>
<input name="sata" value="<?php print $sata; ?>" id="keyword" placeholder="お題 (省略可能)" />
<?php if ($mobile == 0) {?>
<img src="search.gif" onClick="popupKeyPicker()"><span id="keyPickerStatus" class="none"></span>
<?php } ?>
<?php
		} else {
			print "<p>次のエントリーに返信します。</p>".$rtinfo;
		}
?>
<textarea onkeyup="resize_textarea(event)"  name="sas" rows="3" id="status" placeholder="本文"><?php print $fillBody; ?></textarea><br>
<?php if ($isAndroid) {  ?>
<input type="file" accept="image/*" capture="camera" style="margin:0 0 20px 0;" name="file"></input>
<input type="hidden" name="camdata">
<?php } ?>
<input type="submit" name="t8639" value="Haiku!" id="haiku">
<?php
	if ($sata != '') { print '<span id="mail"><a href="mailto:'.$password.'@h.hatena.ne.jp?subject='.$sata.'">メールで送る</a></span>';; }
	if ($rtinfo) { print '<span id="mail"><a href="mailto:'.$password.'.'.$modeparam.'@h.hatena.ne.jp?subject='.$sata.'">メールで返信する</a></span>'; }
	if ($isAndroidOpera) { print '<img src="search_kamaboko.gif" onClick="popupCam()">'; }
?>
</form><hr>
<?php
}
/* ($login) */
if (isset($amazblock) && $amazblock) { print $amazblock; }
if (isset($urlkeyblock) && $urlkeyblock) { print $urlkeyblock; }
print $out;
if ($mobile) { print "<hr>"; }
if ($pagenavi) { printPageNavigator($page); }
if ($mobile == 1) { printKoHaikuFooter(); }
$s = <<<EOM
<div id="keyPicker" class="keyPicker" style="display:none;">
	<div class="keyPickerWrapper">
		<div class="keyPickerBody">
			<big>キーワードピッカー</big><br><br>
			投稿したいキーワードを選んでね。<br><br>
			■お気に入りキーワードから選ぶ<br>
			<div id="keyPickerFromUserMenu"><img src="loading-key.gif"></div><br>
			■人気のキーワードから選ぶ<br>
			<div id="keyPickerFromHotKeys"><img src="loading-key.gif"></div><br>
			■キーワードを検索する<br>
			<form name="keySearch" onsubmit="return keywordSearch(document.keySearch)" method="post"><input name="searchWord" class="keyPickerObject"><input type="submit" value="検索"></form>
			<div id="keySearchResult"></div>
		</div>
		<div id="close"><a href="javascript:void(0);" onclick="popupKeyPicker()">とじる</a></div>
	</div>
</div>
<p id="copy">&copy; 2010-2012 kokuda.org (Akkie)</p>
EOM;
	if ($isAndroidOpera) {
		$s .= getOperacamBoxHTML();
	}
	print preg_replace('/(\n|\t)/', '', $s);
	print ($mobile != 1) ? '<script type="text/javascript" src="http://s.hatena.ne.jp/js/HatenaStar.js"></script><script type="text/javascript">'.getJavaScript().'</script>' : '';
?>
</body></html>
