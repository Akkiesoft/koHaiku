<?php

require_once('amaz.php');
require_once('haikuparser.php');

// HTML_Emoji ライブラリの読み込み
//require_once 'Emoji.php';

function getEntry($entryid)
{
	global $username, $password;
	$req = new HTTP_Request();
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->setBasicAuth($username, $password);
	$req->setURL('http://h.hatena.ne.jp/api/statuses/show/'.$entryid.'.json?body_formats=haiku');
	$res = $req->sendRequest();
	if(PEAR::isError($res)) {
		return $res->getMessage();
	}
	if ($req->getResponseCode() == 200) {
		return $req->getResponseBody();
	} else {
		return $req->getResponseCode();
	}
}

function parseEntry($entry, $bgcol, $opt = '')
{
	global $homeurlBase64, $username, $script, $mobile;

	$spamchksw = ($opt == 'nospamchk') ? 0:1;
	$out = '';
	$delmark = '';
	$rt = '';

	$entryid    = $entry->id;
	$link       = $entry->link;
	$linkencoded = urlencode($link);
	$nick       = htmlspecialchars($entry->user->name);
	$id         = $entry->user->screen_name;
	$keyword    = $entry->keyword;
	$keywordName= $entry->target->title;
	$keywordurl = getKeywordURL($keyword);
	$status     = parseHaikuText($keyword, $entry->haiku_text, $mobile, $spamchksw);
	$cli        = htmlspecialchars($entry->source);
	$date       = date('Y-m-d H:i:s', strtotime($entry->created_at));
	$star       = $entry->favorited;
	$usericon   = $entry->user->profile_image_url;
	$dammylink  = '';

	// Hatena Star
	if ($mobile == 1) {
		$star_str = '<a href="http://s.hatena.ne.jp/star.add?sid=&rks=&uri='.$linkencoded.'&location='.$script.'"><img src="add.gif" /></a> ';
		if (0 < $star && $opt != 'nostar') {
			$tmp = htmlspecialchars($id.'/'.$entryid);
			$star_str .= '<a href="'.$script.'star/'.$tmp.'" class="star">★';
			if (1 < $star) { $star_str .= '×たくさん'; }
			$star_str .= '</a>';
		}
	} else {
		$dammylink = '<a href="'.$link.'" class="star-link"></a>';
		$star_str = '<span class="star">&nbsp;</span>';
	}

	// Reply
	$replyFrom  = "";
	if ($entry->in_reply_to_user_id != "" && $entry->in_reply_to_status_id != "") {
		$targetUser = $entry->in_reply_to_user_id;
		$targetUserIcon = ($mobile == 1) ? '' : '<img src="http://www.st-hatena.com/users/'.substr($targetUser, 0, 2).'/'.$targetUser.'/profile_s.gif" alt="'.$targetUser.'">';
		$replyFrom .= '<a href="e/'.$entry->in_reply_to_status_id.'">←'.$targetUserIcon.$targetUser.'</a><br />';
	}

	$replies = "";
	if ($entry->replies) {
		foreach($entry->replies as $item) {
//			$img = 'http://www.st-hatena.com/users/'.substr($item->user->name, 0, 2).'/'.$item->user->name.'/profile_s.gif';
			$img = preg_replace('/profile/', 'profile_s', $item->user->profile_image_url);
			$replies .= '<a href="e/'.$item->id.'"><img src="'.$img.'" alt="'.$item->user->name.'" /></a> ';
		}
	}

	if ($opt != 'delete' && $id == $username) {
		if ($bgcol == 'pub') $backTo = './';
		if ($bgcol == 'usr') $backTo = 'u/'.$id;
		if ($bgcol == 'key') $backTo = $keywordurl;

		// mobile or other.
		$delmark = ($mobile == 1) ? ' <a href="delete/'.$entryid.'">[X]</a>' : <<<EOM
 <form action="$backTo" method="post" class="il"><input type="hidden" name="eid" value="$entryid" /><input type="image" src="delete.gif" name="del" value="X" onclick="return chkDel()" /></form>
EOM;
	}

	if ($opt != 'delete') {
		$rt = '<a href="e/'.$entryid.'">(Reply)</a></small>';
	}

	if ($mobile == 1) {
		$out     .= <<<EOM
<div class="entry $bgcol"><a href="$keywordurl" class="keyword">$keywordName</a> <span class="star">$star_str</span><br>$replyFrom $status<br><small><a href="u/$id">$nick ($id)</a> @ $date $cli$delmark $rt$replies $dammylink</div>
EOM;

	} else {
		$out     .= <<<EOM
<div class="entry $bgcol"><div class="entryuser"><a href="u/$id"><img src="$usericon" alt="$id" class="icon"></a> <a href="$keywordurl" class="keyword">$keywordName</a><span class="star">$star_str</span></div><div class="entrybody">$replyFrom $status<br><small>$nick ($id) @ $date $cli$delmark $rt$replies $dammylink</div></div>
EOM;
// <div class="entry $bgcol"><div class="entryuser"><a href="u/$id"><img src="$usericon" alt="$id" class="icon"></a></div><div class="entrybody"><a href="k/$keywordurl" class="keyword">$keyword</a><div class="star">$star_str</div>$replyFrom $status<small>$id @ $date $cli$delmark $rt$replies $dammylink</div></div>

	}
	return $out;
}

function parseEntries($entries, $color = 'usr', $showProfile = 0)
{
	global $mobile, $ngid, $ngkey, $access_key_id, $secret_access_key;
	$out = "";
	$cnt = 0;
	$bg = $color;

	$addFavorite = ''; //'<a href="/">お気に入りに追加';
	if ($showProfile) {
		if ($color == 'usr' || ($color == 'key' && $match = preg_match('/^id\:([0-9A-Za-z-_]+)$/', $showProfile))) {
			$id = substr($showProfile, 3);
			$profile = json_decode(file_get_contents('http://h.hatena.ne.jp/api/friendships/show/id:'.$id.'.json'));
			$nick = htmlspecialchars($profile->name);
			$icon = $profile->profile_image_url;
			$fans = $profile->followers_count;
			$ngided = '';
			if ($ngid && mb_strpos($ngid, $id) !== FALSE) {
				$icon = 'http://8639.tk/5819/baloon.gif';
				$remark = 'NGIDに登録されています。';
			} else {
				$reportid   = ($color == 'key') ? 'id%2F'.$id : $id.'%2F';
				$reportnick = rawurlencode($nick);
				$remark = '<a href="u/'.$id.'">Entries</a> / '.
							'<a href="f/'.$id.'">Favorites</a> / '.
							'<a href="k/id:'.$id.'">About</a><br><br>'.
					/*		'<a href="/">NGIDに追加する</a> '.*/
							'<a href="http://www.hatena.ne.jp/faq/report/haiku?location=http%3A%2F%2Fh.hatena.ne.jp%2F'.$reportid.'&target_label='.$reportnick.'&target_url=http%3A%2F%2Fh.hatena.ne.jp%2F'.$reportid.'">はてなに通報する</a>';
			}
			$out .= '<div id="ibox"><div id="asinpic"><img src="'. $icon .'" alt="'. $id .'"></div><div id="asininfo">'.
					$nick .'<br><div id="asindetail">id:'. $id .'<br>'. $fans .' fans<br>'.$remark.'</div></div></div>';
		}
		else if ($color == 'key') {
			$out = '<div id="ibox">';
			$modeparam = mb_convert_encoding($_GET['param'], 'UTF-8', 'auto');
			if (preg_match('/^asin\:([a-zA-Z0-9]+)$/', $modeparam, $matches)) {
				// ASIN page.
				$out .= getAmazonBlock($matches[1], $access_key_id, $secret_access_key);
			}
			else if (preg_match('/^https?\:\/\//', $modeparam, $matches)) {
				// URL Keyword.
				$out .= '<div id="asindetail">リンク先: <a href="'.$modeparam.'" target="_blank">'.$modeparam.'</a><br>'.$addFavorite.'</div>';
			}
			else {
				// Normal Keyword
				$i = rawurlencode($showProfile);
				$j = preg_replace('/\%/', '%25', $i);
				$out .= $showProfile.'<div id="asindetail"><br>'.$addFavorite.
						'<a href="http://www.hatena.ne.jp/faq/report/haiku?location=http%3A%2F%2Fh.hatena.ne.jp%2Fkeyword%2F'.$j.'&target_label='.$i.'&target_url=http%3A%2F%2Fh.hatena.ne.jp%2Fkeyword%2F'.$j.'">はてなに通報する</a>'.
						'</div>';
			}
			$out .= '</div>';
		}
	}

	foreach ($entries as $entry) {
		if ($mobile) {
			$bg = ($cnt++ % 2) ? 'white' : $color;
		}
		$skipflag = 0;
		if (mb_strpos($ngkey, $entry->keyword) !== FALSE) { $skipflag++; }
		if (mb_strpos($ngid, $entry->user->screen_name) !== FALSE) { $skipflag++; }
		if (!$skipflag) {
			$out .= parseEntry($entry, $bg);
		}
	}
	return $out;
}


function deleteEntry($entryid)
{
	global $username, $password;
	$req  = new HTTP_Request();
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->setBasicAuth($username, $password);
	$req->setURL('http://h.hatena.ne.jp/api/statuses/destroy/'.$entryid.'.json');
	$res = $req->sendRequest();
	if(PEAR::isError($res)) {
		return $res->getMessage();
	}
	return;
}

function postEntry($keyword="", $status, $file, $rtid = 0)
{
	global $username, $password, $isAndroid;
	$req  = new HTTP_Request();
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->setBasicAuth($username, $password);
	if ($file) {
		if ($file['type'] == 'image/jpeg'
			|| $file['type'] == 'image/gif'
			|| $file['type'] == 'image/png') {
			$req->addFile('file',$file['tmp_name']);
		}
	}
	$req->addPostData('keyword', $keyword);
	$req->addPostData('source', 'koHaiku');
	if ($status) {
		$req->addPostData('status', $status);
	}
	if ($rtid) {
		$req->addPostData('in_reply_to_status_id', $rtid);
	}
	$req->setURL('http://h.hatena.ne.jp/api/statuses/update.json');
	$res = $req->sendRequest();
	if(PEAR::isError($res)) {
		return $res->getMessage();
	}
	return;
}


function getStarData($entryurl)
{
	$starcount = 0;
	$starinfo = file_get_contents('http://s.hatena.ne.jp/entry.json/?uri=http://h.hatena.ne.jp/'.$entryurl);
	$starinfo = json_decode($starinfo);
	$starout = "";
	if (isset($starinfo->entries[0]->colored_stars)) {
		foreach ($starinfo->entries[0]->colored_stars as $color) {
			$starcolor = $color->color;
			$userstar = array();
			foreach ($color->stars as $item) {
				if (! isset( $userstar[$item->name])) {$userstar[$item->name] = 0; }
				$userstar[$item->name] += (isset($item->count)) ? $item->count : 1;
			}
			foreach ($userstar as $name => $count) {
				$starout .= $name . ' <span class="'.$starcolor.'star">★×' . $count . "</span><br>\n";
				$starcount += $count;
			}
		}
	}
	$userstar = array();
	foreach ($starinfo->entries[0]->stars as $item) {
		if (! isset( $userstar[$item->name])) {$userstar[$item->name] = 0; }
		$userstar[$item->name] += (isset($item->count)) ? $item->count : 1;
	}
	foreach ($userstar as $name => $count) {
		$starout .= $name . ' <span class="star">★×' . $count . "</span><br>\n";
		$starcount += $count;
	}
	preg_match('/(\w+)\/(\w+)/', $entryurl, $match);
	$json = getEntry($match[2]);
	$out = parseEntry(json_decode($json), "#eeefff", 'nostar');
	$out .= <<<EOM
<hr><p>■このエントリーについたスター($starcount)<br>$starout</p><p><small>※ブラウザの「戻る」機能で戻ってね</small></p>
EOM;
	return $out;
}

function printKoHaikuHeader()
{
	global $script, $username, $mobile, $login;

	if ($login) { $welcomemessage = "Welcome $username !"; }

	if ($mobile == 1) {
		$head = '<h1>koHaiku</h1><small>'.$welcomemessage.'&nbsp;&nbsp;<a href="#footmenu">▼Menu</a></small>';
	} else {
		$head = <<<EOM
<h1><img src="baloon.gif" alt="koHaiku" onclick="starIconMode()" id="userIconMode" data-flag='0'><img src="logo.gif" alt="koHaiku"></h1>
<ul id="headmenu">
	<span id="headmenushowing" class="none"></span>
	<li id="public"><a href="./">Public</a></li>
	<li id="user"><a href="javascript:void(0);" onclick="popupElement('usermenu', '$username');">User</a></li>
	<li id="keyword"><a href="javascript:void(0);" onclick="popupElement('hotkeyslist', '');">Keyword</a></li>
</ul>
<div id="hotkeyslist" class="popupbox" style="display:none;">
	<div id="hotkeyslistbody"><div class="center"><img src="loading-key.gif" alt="Loading..." id="hotkeyslistloading"></div></div>
</div>
<div id="usermenu" class="popupbox" style="display:none;">
EOM;
		if ($login) {
			$head   .= <<<EOM
<p>{$welcomemessage}</p>
<h3>User Menu</h3>
<ul>
	<li><a href="u/{$username}">Entries</a></li>
	<li><a href="f/{$username}">Favorites</a></li>
	<li><a href="k/id:{$username}">About</a></li>
	<li><a href="settings.php">設定</a></li>
	<li><a href="login.php?op=logout">ログアウト</a></li>
</ul>
<h3>Following Keywords</h3>
<div id="usermenubody"><div class="center"><img src="loading-user.gif" alt="Loading..." id="usermenuloading"></div></div>
EOM;
		} else {
			$head .= '<ul><li><a href="login.php">ログインする</a></li></ul>';
		}
		$head .= "</div>";
	}
	$head = preg_replace('/(\n|\t)/', '', $head);
	print $head;
	return;
}

function printKoHaikuFooter() {
	global $username, $login;
	$foot = '<div id="foot"><a href="./" accesskey="1" name="footmenu">[1]Public</a><br><a href="k/" accesskey="2">[2]Hot Keywords</a><br>';

	$foot .= ($login) ? '<a href="u/" accesskey="3">[3]Favorite Keywords</a><br><a href="u/'.$username.'" accesskey="4">[4]'.$username."'".'s Entries</a><br><a href="f/'.$username.'" accesskey="5">[5]'.$username."'".'s Favorites</a><br><a href="k/id:'.$username.'" accesskey="6">[6]'.$username."'".'s About</a><br><a href="login.php?op=logout" accesskey="0">[0]Logout</a>'
					  : '<a href="login.php" accesskey="0">[0]Login</a>';
	$foot .= '</div>';
	print $foot;
	return;
}

function printPageNavigator($entries)
{
	$baseurl = preg_replace('/(\?|\&)reftime=([-+,0-9]+)/', '', $_SERVER["REQUEST_URI"]);
	$optstr = (strpos($baseurl, '?') === FALSE) ? '?' : '&';

	$olderBase = '-'.strtotime($entries[count($entries)-1]->created_at).',1';

	print '<p style="text-align:center;">';
	print '<a href="'. $baseurl . $optstr . 'reftime='. $olderBase .'">過去のエントリーを見る</a><br><br>';
	print "</p>";
	return;
}

function getKeywordList($mode, $user = "")
{
	$start_time = microtime(true);
	$req  = new HTTP_Request();
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	if ($mode == "fav") {
		$req->setURL('http://h.hatena.ne.jp/api/statuses/keywords/'.$user.'.json?without_related_keywords=1');
	} else if ($mode == "hot") {
		$req->setURL('http://h.hatena.ne.jp/api/keywords/hot.json?without_related_keywords=1');
	}
	$res = $req->sendRequest();
	if(PEAR::isError($res)) {
		return $res->getMessage();
	}
	$time = round((microtime(true) - $start_time) * 100) / 100;

	$result = "<ul>";
	$items = json_decode($req->getResponseBody());
	foreach($items as $item) {
		$link = getKeywordURL($item->word);
		$result .= "<li><a href=\"".$link."\">".$item->title."</a></li>";
	}
	return $result."</ul><small>※キーワード取得にかかった時間:".$time."秒</small>";
}

function printCSS($mobile) {
	$out = file_get_contents("style.css");
	if ($mobile == 1) {
		$out .= file_get_contents("mobile.css");
	} else if ($mobile == 2) {
		$out .= file_get_contents("dsi.css");
	} else if ($mobile == 0) {
		$out .= file_get_contents("touch.css");
	}
	$out = preg_replace('/\/\* (.+) \*\//', '', $out);
	$out = preg_replace('/(\n|\t)/', '', $out);
	$out = preg_replace('/, /', ',', $out);
	print $out;
	return;
}
function getJavaScript() {
	global $isAndroidOpera;
	$out = file_get_contents("scripts.js");
	if ($isAndroidOpera) {
		$out .= "\n".file_get_contents("operacam.js");
	}
	$out = preg_replace('/\/\* (.+) \*\//', '', $out);
	$out = preg_replace('/(\n|\t)/', '', $out);
	$out = preg_replace('/, /', ',', $out);
	return $out;
}

?>
