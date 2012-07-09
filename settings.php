<?php
	if (isset($_COOKIE["koHaiku"])) {
		$cookie = $_COOKIE["koHaiku"];
		list($username,$password) = explode(",", $_COOKIE['koHaiku']);
		$login = 1;
	} else {
		header('Location:login.php');
	}

	require_once 'init.php';
	require_once 'func.php';

	if (!$usedb) {
		print "This koHaiku is not use database. NGID and NG keyword functions are disabled.";
		exit;
	}

	if (isset($_POST['NG'])) {
		/* 設定 */
		$ngid  = htmlspecialchars($_POST['ngid']);
		$ngkey = htmlspecialchars($_POST['ngkey']);

		if (!$NGSetting) {
			/* 新規 */
			$param  = "'" . $username . "', ENCODE('" . $ngid . "','kohaiku'), ENCODE('" . $ngkey . "','kohaiku')";
			mysql_query('insert into kohaiku_ng values('.$param.')', $mysql);
		} else {
			/* update */
			$param  = "ngid = ENCODE('" . $ngid . "','kohaiku'), ngkey = ENCODE('" . $ngkey . "','kohaiku')";
			mysql_query('update kohaiku_ng set '.$param.' where hatenaid = "'.$username.'"', $mysql);
		}
		mysql_close($mysql);
		header('Location:settings.php');
		exit;
	}

	/* 取得 */
	$ngid = $NGSetting->d_ngid;
	$ngkey = $NGSetting->d_ngkey;

	mysql_close($mysql);

	$mobilehead = (!$mobile) ? '<meta name="viewport" content="width=device-width, minimum-scale=1.0"><meta name="format-detection" content = "telephone=no"><link rel="apple-touch-icon" href="'.$script.'iPhoneIcon.png">'."\n" : '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8"><meta http-equiv="Expires" content="0"><meta http-equiv="Content-Style-Type" content="text/css"><base href="<?php print $script; ?>"><title>koHaiku</title><?php print $mobilehead; ?>
<style type="text/css"><?php printCSS($mobile); ?></style>
</head>
<body>
<?php printKoHaikuHeader(); ?>
<h2>設定</h2>
<form action="settings.php" method="post">
<p>NG ID(改行で区切る)</p>
<textarea name="ngid" style="width:90%;height:10em;"><?php print $ngid; ?></textarea>
<p>NG Keyword(改行で区切る)</p>
<textarea name="ngkey" style="width:90%;height:10em;"><?php print $ngkey; ?></textarea>
<p><input type="submit" name="NG"></p>
</form>

<?php if ($mobile) { print "<hr>";printKoHaikuFooter(); } ?>
<p id="copy">&copy; 2011 kokuda.org (Akkie)</p>
<?php
	print (!$mobile) ? '<script type="text/javascript" src="http://s.hatena.ne.jp/js/HatenaStar.js"></script><script type="text/javascript">'.getJavaScript().'</script>' : '';
?>

</body></html>
