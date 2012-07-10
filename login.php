<?php
	require_once 'init.php';
	require_once 'func.php';

	if (isset($_GET['op'])) {
		$op = htmlspecialchars($_GET['op']);
		if ($op == 'logout') {
			setcookie('koHaiku', "", 0, '/'.$ver.'/', $domain);
			header("Location:./");
		}
	}

	if ($login) {
		header("Location:./");
	}

	$result = "";
	if (isset($_POST['submit'])) {
		$username = $_POST['yuuzaa'];
		$password = $_POST['pasuwa'];
		$req = new HTTP_Request();
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$req->setBasicAuth($username, $password);
		$req->setURL('http://h.hatena.ne.jp/api/statuses/user_timeline.xml');
		$res = $req->sendRequest();
		if(PEAR::isError($res)) {
			print $res->getMessage();
		}
		$ret = $req->getResponseCode();
		if ($ret == 401) {
			$result = "Authentication Error.";
		} else if ($ret == 200) {
			$cookievalue   = $username.",".$password;
			$cookietimeout = time() + 30 * 86400;
			setcookie('koHaiku', $cookievalue, $cookietimeout, '/'.$ver.'/', $domain);
			header("Location:$script");
		} else {
			$result = "Error!($ret)";
		}
	}
?>
<!DOCTYPE html>
<html lang="ja"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta http-equiv="Expires" content="0"><meta http-equiv="Content-Style-Type" content="text/css"><base href="<?php print $script; ?>"><title>koHaiku</title><?php print $mobilehead; ?><style type="text/css"><?php printCSS($mobile); ?></style></head>
<body><h1><img src="logo.gif" alt="koHaiku" /></h1>
<p style="text-align:center;"><small>簡易はてなハイククライアント</small></p>
<form id="login" action="login.php" method="POST" style="margin:0.5em 2px;padding:3px; ">はてなID:<br /><input name="yuuzaa" istyle="3" mode="alphabet" /><br />APIパスワード:<br /><input name="pasuwa" istyle="3" mode="alphabet" type="password" /><br /><input id="button" type="submit" name="submit" value="ログイン" />
<?php if ($result){print '<p style="color:red;">'.$result.'</p>';} ?></form>
<p id="copy">&copy; 2010-2012 kokuda.org (Akkie)</p>
</body></html>
