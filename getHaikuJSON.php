<?php
	require_once('config.php');

	/* DBÚ‘± */
	if ($usedb) {
		if (! $mysql = mysql_connect($dbaddr, $dbuser, $dbpass) ) {
			print '{DB‚ÌÚ‘±‚ÉŽ¸”s‚µ‚Ü‚µ‚½cB}';
			exit;
		}
		mysql_select_db($dbname, $mysql);

		$query = "select * from kohaiku_cache";
		$res = mysql_query($query, $mysql);
		$cache = array();
		while ($line = mysql_fetch_object($res)) {
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
				mysql_query('update kohaiku_cache set lastupdate="'.$expire."\", cachedata='".$data."' where type = \"hotkeyslist\"", $mysql);
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