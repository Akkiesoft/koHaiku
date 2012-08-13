<?php
	require_once 'HTTP/Request.php';
	mb_http_output ('UTF-8');
	date_default_timezone_set('Asia/Tokyo');

	require_once('config.php');

	$username = '';
	$password = '';
	$login = 0;
	
	// Check login (Cookie)
	if (isset($_COOKIE["koHaiku"])) {
		$cookie = $_COOKIE["koHaiku"];
		list($username,$password) = explode(",", $_COOKIE['koHaiku']);
		$login = 1;
	}

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
		/* DB接続 */
		if (! $mysql = mysql_connect($dbaddr, $dbuser, $dbpass) ) {
			print 'DBの接続に失敗しました…。';
			exit;
		}
		mysql_select_db($dbname, $mysql);

		$query = "select DECODE(ngid,'kohaiku') as d_ngid, DECODE(ngkey,'kohaiku') as d_ngkey, kohaiku_settings.settings_json as json from kohaiku_ng join kohaiku_settings using(uid) where kohaiku_settings.hatenaid = \"".$username.'"';
		$res = mysql_query($query, $mysql);
		$data = mysql_fetch_object($res);
		mysql_free_result($res);

		$ngid = $data->d_ngid;
		$ngkey = $data->d_ngkey;
		$settings = json_decode($data->json);
	}

	$mobilehead = '';
	if ($mobile == 0) {
		$mobilehead = $viewport.'<meta name="format-detection" content = "telephone=no"><link rel="apple-touch-icon" href="'.$script.'iPhoneIcon.png">'."\n";
	} else if ($mobile == 2) {
		$mobilehead = $viewport;
	}
?>
