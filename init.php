<?php
	require_once 'HTTP/Request.php';
	mb_http_output ('UTF-8');
	date_default_timezone_set('Asia/Tokyo');

	require_once('config.php');

	// Check UA ( 0==PC/touch, 1==Mobile, 2==DSi )
	$ua = $_SERVER['HTTP_USER_AGENT'];
	$pattern = '/(DoCoMo)|(UP\.Browser)|(J-PHONE)|(Vodafone)|(Softbank)/';
	$mobile = (preg_match($pattern, $ua)) ? 1 : 0;
	$pattern = '/Nintendo DSi/';
	if (!$mobile) {
		$mobile = (preg_match($pattern, $ua)) ? 2 : 0;
		$viewport = (preg_match($pattern, $ua)) ? '240' : 'device-width, minimum-scale=1.0, user-scalable=no';
		$viewport = '<meta name="viewport" content="width='.$viewport.'">';
	}
	$pattern = '/Android/';
	$isAndroid = (preg_match($pattern, $ua)) ? 1:0;
	$isAndroidOpera = 0;
	if ($isAndroid) {
		$pattern = '/(Opera Mobi)/';
		if (preg_match($pattern, $ua)) {
			$isAndroidOpera = 1;
			include('operacam.php');
		}
	}

	$ngid = '';
	$ngkey = '';

	if ($login && $usedb) {
		/* DBÚ‘± */
		if (! $mysql = mysql_connect($dbaddr, $dbuser, $dbpass) ) {
			print 'DB‚ÌÚ‘±‚ÉŽ¸”s‚µ‚Ü‚µ‚½cB';
			exit;
		}
		mysql_select_db($dbname, $mysql);

		$query = "select hatenaid, ngid,ngkey,DECODE(ngid,'kohaiku') as d_ngid, DECODE(ngkey,'kohaiku') as d_ngkey from kohaiku_ng where hatenaid = \"".$username.'"';
		$res = mysql_query($query, $mysql);
		$NGSetting = mysql_fetch_object($res);
		mysql_free_result($res);

		$ngid = $NGSetting->d_ngid;
		$ngkey = $NGSetting->d_ngkey;
	}

	$mobilehead = '';
	if ($mobile == 0) {
		$mobilehead = $viewport.'<meta name="format-detection" content = "telephone=no"><link rel="apple-touch-icon" href="'.$script.'iPhoneIcon.png">'."\n";
	} else if ($mobile == 2) {
		$mobilehead = $viewport;
	}
?>
