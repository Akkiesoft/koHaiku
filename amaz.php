<?php
function urlencode_rfc3986($str) {
	// RFC3986形式でURLエンコード
	// Based on:
	//   http://d.hatena.ne.jp/p4life/20090510/1241954889
    return str_replace('%7E', '~', rawurlencode($str));
}

function getAmazonItemXMLbyASIN($asin, $awskey, $secret) {
	// Based on:
	//   http://d.hatena.ne.jp/p4life/20090510/1241954889

	$baseurl = 'http://ecs.amazonaws.jp/onca/xml';
	$params = array();
	$params['AWSAccessKeyId'] = $awskey;
	$params['AssociateTag']   = 'akkienisshi-22';
	$params['IdType']         = 'ASIN';
	$params['ItemId']         = $asin;
	$params['Operation']      = 'ItemLookup';
	$params['ResponseGroup']  = 'ItemAttributes,Images';
	$params['Service']        = 'AWSECommerceService';
	$params['Timestamp']      = gmdate('Y-m-d\TH:i:s\Z');	// Timestamp (ISO8601 / UTC(GMT))
	$params['Version']        = '2010-11-01';

	// canonical stringを作成
	$canonical_string = '';
	foreach ($params as $k => $v) {
	    $canonical_string .= '&'.urlencode_rfc3986($k).'='.urlencode_rfc3986($v);
	}
	$canonical_string = substr($canonical_string, 1);

	// 署名を作成
	$parsed_url = parse_url($baseurl);
	$string_to_sign = "GET\n{$parsed_url['host']}\n{$parsed_url['path']}\n{$canonical_string}";
	$signature = base64_encode(hash_hmac('sha256', $string_to_sign, $secret, true));

	$url = $baseurl.'?'.$canonical_string.'&Signature='.urlencode_rfc3986($signature);

	return simplexml_load_file($url);
}

function getAmazonBlock($asin, $awskey, $secret) {
	global $baseurl, $mobile;
	$out = '';

	$xml  = getAmazonItemXMLbyASIN($asin, $awskey, $secret);

	if ($xml->Items->Request->Errors) {
		return;
	}

	$item = $xml->Items->Item;
	
	$link  = 'http://www.amazon.co.jp/exec/obidos/ASIN/' . $asin . '/akkienisshi-22/ref=nosim/';
	$title = $item->ItemAttributes->Title;
	$image = $item->SmallImage->URL;
	$imgx  = $item->SmallImage->Width;
	$imgy  = $item->SmallImage->Height;
	$price = $item->ItemAttributes->ListPrice->FormattedPrice;
	$label .= $item->ItemAttributes->Publisher;

	$author = '';
	switch ($item->ItemAttributes->ProductGroup) {
		case 'Video':
		case 'DVD':
			$authorTarget = $item->ItemAttributes->Actor;
			break;
		case 'Music':
			$authorTarget = $item->ItemAttributes->Artist;
			break;
		case 'Book';
		case 'Kitchen':
			$authorTarget = $item->ItemAttributes->Author;
			break;
	}
	$authorCnt = count($authorTarget);
	for ($i = 0; $i < $authorCnt; $i++) {
		$author .= '<a href="' . $baseurl . 'k/' . rawurlencode($authorTarget[$i]) . '">' . $authorTarget[$i] . '</a>';
		if ($i == 0) { $authorMobile = $author; }
		if ($i < $authorCnt - 1) { $author .= ', '; }
	}

	if ($mobile) {
		$out .= '<a href="' . $link . '"><img src="' . $image . '" title="' . $title . '" width="' . $imgx . '" height="' . $imgy . '" align="left"></a>';
		$out .= '<a href="'.$link.'" id="asintitle">' . $title . '</a><br><small>';
		if ($author) { $out .= $authorMobile . " 他<br>"; }
		if ($price)  { $out .= $price  . "<br>"; }
		$out .= '</small><br clear="all">';
	} else {
		$out .= '<div id="asinpic"><a href="' . $link . '"><img src="' . $image . '" title="' . $title . '" width="' . $imgx . '" height="' . $imgy . '"></a></div>';
		$out .= '<div id="asininfo">';
		$out .= '<a href="'.$link.'" id="asintitle">' . $title . '</a><br>';
		$out .= '<div id="asindetail">';
		if ($author) { $out .= $author . "<br>"; }
		if ($label)  { $out .= $label  . "<br>"; }
		if ($price)  { $out .= $price  . "<br>"; }
		$out .= '</div></div>';
	}

	return $out;
}

?>
