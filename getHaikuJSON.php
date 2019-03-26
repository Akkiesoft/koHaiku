<?php
	require_once('config.php');

	/* DBÚ‘± */
	if ($usedb) {
		if (! $mysqli = mysqli_connect($dbaddr, $dbuser, $dbpass) ) {
			print '{DB‚ÌÚ‘±‚ÉŽ¸”s‚µ‚Ü‚µ‚½cB}';
			exit;
		}
		mysqli_select_db($dbname, $mysqli);

		$query = "select * from kohaiku_cache";
		$res = mysqli_query($query, $mysqli);
		$cache = array();
		while ($line = mysqli_fetch_object($res)) {
			$cache[$line->type] = array(
				expire => intval($line->lastupdate),
				data => $line->cachedata
			);
		}
		$now = time();
		$expire = $now + 300;
	} else {
		$now = time();
		$cache['hotkeyslist']['expire'] = 0;
	}

	$url = '';
	if ($_GET['type'] == "hotkeyslist") {
		if ($cache['hotkeyslist']['expire'] < $now) {
			$data = file_get_contents("http://h.hatena.ne.jp/api/keywords/hot.json?without_related_keywords=1");
			if ($usedb) {
				mysqli_query('update kohaiku_cache set lastupdate="'.$expire."\", cachedata='".$data."' where type = \"hotkeyslist\"", $mysqli);
			}
		} else {
			$data = $cache[$_GET['type']]['data'];
		}
	}
	else if ($_GET['type'] == "usermenu") {
		$user = htmlspecialchars($_GET['user']);
		$data = file_get_contents("http://h.hatena.ne.jp/api/statuses/keywords/".$user.".json?without_related_keywords=1");
	}
	else if ($_GET['type'] == "search") {
		$word = rawurlencode($_GET['word']);
		$data = file_get_contents("http://h.hatena.ne.jp/api/keywords/list.json?word=".$word."&without_related_keywords=1");
	}

	header("Content-type: application/json");
	print $data;
?>
