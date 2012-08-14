<?php
	require_once 'init.php';
	if (!$login) {
		header('Location:login.php');
		exit;
	}
	if (!$usedb) {
		print "This koHaiku is not use database. NGID and NG keyword functions are disabled.";
		exit;
	}
	require_once 'func.php';

	if (isset($_POST['setting'])) {
		/* 設定 */
		if (! $mysql = mysqli_connect($dbaddr, $dbuser, $dbpass, $dbname) ) {
			print 'DBの接続に失敗しました…。';
			exit;
		}

		$ngid  = htmlspecialchars($_POST['ngid']);
		$ngkey = htmlspecialchars($_POST['ngkey']);
		$json = json_encode(array(
			'showpict' => (isset($_POST['opt_showpict'])) ? htmlspecialchars($_POST['opt_showpict']) : '0'
		));

		if (!$settings) {
			/* 新規 */
			$param  = "'" . $username . "', ENCODE('" . $ngid . "','kohaiku'), ENCODE('" . $ngkey . "','kohaiku'), '" . $json . "'";
			mysqli_query($mysql, 'insert into kohaiku values('.$param.')');
		} else {
			/* update */
			$param  = "ngid = ENCODE('" . $ngid . "','kohaiku'), ngkey = ENCODE('" . $ngkey . "','kohaiku'), json = '" . $json . "'";
			mysqli_query($mysql, 'update kohaiku set '.$param.' where hatenaid = "'.$username.'"');
		}
		mysqli_close($mysql);
		header('Location:settings.php');
		exit;
	}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8"><meta http-equiv="Expires" content="0"><meta http-equiv="Content-Style-Type" content="text/css"><base href="<?php print $script; ?>"><title>koHaiku</title><?php print $mobilehead; ?>
<style type="text/css"><?php printCSS($mobile); ?></style>
</head>
<body>
<?php printKoHaikuHeader(); ?>
<div id="ibox">設定</div>
<form action="settings.php" method="post">
<div class="entry pub"><div class="entryuser">
NG ID(改行で区切る)<br>
<textarea name="ngid" style="width:90%;height:10em;"><?php print $ngid; ?></textarea>
</div></div>
<div class="entry pub"><div class="entryuser">
<p>NG Keyword(改行で区切る)</p>
<textarea name="ngkey" style="width:90%;height:10em;"><?php print $ngkey; ?></textarea>
</div></div>
<div class="entry pub"><div class="entryuser">
<p>画像の表示方法</p>
<label><input type="radio" name="opt_showpict" value="0"<?php if($opt_showpict=='0'){print " checked";} ?>> 通常表示</label><br>
<label><input type="radio" name="opt_showpict" value="1"<?php if($opt_showpict=='1'){print " checked";} ?>> サムネイル表示</label><br>
<label><input type="radio" name="opt_showpict" value="2"<?php if($opt_showpict=='2'){print " checked";} ?>> リンク</label><br>
※サムネイル表示はフォトライフのみ対応。その他の画像はリンクになります<br>※ケータイは通常表示を選択してもサムネイル表示になります
</div></div>
<div class="entry pub"><div class="entryuser">
<input type="submit" name="setting">
</div></div>
</form>

<?php if ($mobile) { print "<hr>";printKoHaikuFooter(); } ?>
<p id="copy">&copy; 2010-2012 kokuda.org (Akkie)</p>
<?php
	print (!$mobile) ? '<script type="text/javascript" src="http://s.hatena.ne.jp/js/HatenaStar.js"></script><script type="text/javascript">'.getJavaScript().'</script>' : '';
?>

</body></html>
