function chkDel()
{
	return confirm('このエントリを削除しても良いですか？');
}
function showImage(link, img, alt, id)
{
	id.innerHTML='<a href="'+link+'"><img src="'+img+'" alt="'+alt+'"></a>';
}

function resize_textarea(ev){
/*    if (ev.keyCode != 13) return;*/
	var textarea = ev.target || ev.srcElement;
	var value = textarea.value;
	var lines = 1;
	for (var i = 0, l = value.length; i < l; i++){
		if (value.charAt(i) == '\n') lines++;
	}
	if (lines < 3) lines = 3;
	textarea.setAttribute("rows", lines);
/*      window.status = lines; */
}

function getPageSize() {
	/* from lightbox2.js v2.05 by Lokesh Dhakar ( http://www.lokeshdhakar.com/projects/lightbox2/ ) */
	var xScroll, yScroll;

	if (window.innerHeight && window.scrollMaxY) {
		xScroll = window.innerWidth + window.scrollMaxX;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ /* all but Explorer Mac */
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else { /* Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari */
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}

	var windowWidth, windowHeight;

	if (self.innerHeight) {	/* all except Explorer */
		if(document.documentElement.clientWidth){
			windowWidth = document.documentElement.clientWidth;
		} else {
			windowWidth = self.innerWidth;
		}
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { /* Explorer 6 Strict Mode */
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { /* other Explorers */
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}

	/* for small pages with total height less then height of the viewport */
	if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else { 
		pageHeight = yScroll;
	}

	/* for small pages with total width less then width of the viewport */
	if(xScroll < windowWidth){
		pageWidth = xScroll;
	} else {
		pageWidth = windowWidth;
	}
	return [pageWidth,pageHeight];
}

function createXMLHttpRequest()
{
	var XMLhttpObject = null;
	try {
		XMLhttpObject = new XMLHttpRequest();
	} catch(e) {
		try{
			XMLhttpObject = new ActiveXObject("Msxml2.XMLHTTP");
		} catch(e) {
			try {
				XMLhttpObject = new ActiveXObject("Microsoft.XMLHTTP");
			} catch(e) {
				return null;
			}
		}
	}
	return XMLhttpObject;
}

/* JSONのデータを解析して表示 */
function parseJSONforMenu(jsData)
{
	var data = eval("("+jsData+")");
	var resultData = "<ul>";
	for(var i=0; i<data.length; i++) {
		var keyword = data[i].word;
		var keymode = 'k/';
		if (0 <= keyword.search(/[#\/]/)) {
			keymode = '?mode=key&param=';
		}
		var iLink  = keymode+encodeURIComponent(keyword);
		var iTitle = data[i].title;
		resultData += "<li><a href=\""+iLink+"\">"+iTitle+"</a></li>";
	}
	resultData += "</ul>";
	return resultData;
}

/* JSONのデータを解析して表示 */
function parseJSONforKeyPicker(jsData)
{
	var data = eval("("+jsData+")");
	var resultData = '<form><select name="select" class="keyPickerObject">';
	for(var i=0; i<data.length; i++) {
		var iTitle = data[i].title;
		resultData += "<option value=\""+iTitle+"\">"+iTitle+"</option>";
	}
	if (i == 0) { return false; }
	resultData += '</select><input type="button" onclick="setKeyword(this.form)" value="選択"></form>';

	return resultData;
}

/* モロモロ設定モノ */
Hatena.Star.SiteConfig = { entryNodes: { '.entry': { uri: '.star-link', title: '.keyword', container: '.star' } } };
Hatena.Star.PortalURL = 'http://8639.tk/5819/u/';


function popupElement(element, UserName) {
	var showing = document.getElementById('headmenushowing').className;
	if (showing != 'none') {
		document.getElementById(showing).style.display = 'none';
		if (showing == element) {
			document.getElementById('headmenushowing').className = 'none';
			return;
		}
	}
	var now = document.getElementById(element).style;
	if (now.display == 'none') {
		now.display = 'block';
		document.getElementById('headmenushowing').className = element;
	}
}

/* キーワードピッカー */
function popupKeyPicker() {
	var keyPicker = document.getElementById('keyPicker').style;
	var keyPickerStatus = document.getElementById('keyPickerStatus');
	if (keyPickerStatus.className != 'none') {
		keyPicker.display = 'none';
		keyPickerStatus.className = 'none';
		return;
	}
	if (keyPicker.display == 'none') {
		keyPicker.display = 'block';
		keyPicker.height = getPageSize()[1] + 'px';
		keyPickerStatus.className = 'enabled';
	}
}

function setKeyword(form) {
	var index = form.select.selectedIndex;
	var keyword = form.select.options[index].text;
	document.haikuForm.keyword.value = keyword;
	window.scrollTo(0,1);
	popupKeyPicker();
}

function keywordSearch(form) {
	var searchWord = form.searchWord.value;
	var keySearchResult = document.getElementById("keySearchResult");
	if (!searchWord) { return false; }
	keySearchResult.innerHTML = '<img src="loading-key.gif">';
	httpObj = createXMLHttpRequest();
	if (httpObj) {
		httpObj.open("GET","getHaikuJSON.php?type=search&word=" + searchWord, true);
		httpObj.send(null);
		httpObj.onreadystatechange = function() {
			if ( (httpObj.readyState == 4) && (httpObj.status == 200) ) {
				var result = parseJSONforKeyPicker(httpObj.responseText);
				if (result == false) {
					result = 'キーワードが見つかりませんでした。';
				}
				keySearchResult.innerHTML = result;
			}
		}
	}
	return false;
}


/* 自動スクロール */
function doScroll() { if (window.pageYOffset === 0) { window.scrollTo(0,1); } }

window.onload = function() {
	setTimeout(doScroll, 100);
	var iconFlag = false;

	var url = window.location.href;
	if (url.search(/(\?|\&)askkey/) >= 0) {
		popupKeyPicker();
	}

	/* 非同期でデータを読んでおく系(Flollowing Keywords / Hot keywords) */
	var username = document.cookie.split('%2C')[0].split('=')[1];
	var keyPickerReady = 0;
	if (username) {
		httpObj = createXMLHttpRequest();
		if (httpObj) {
			httpObj.open("GET","getHaikuJSON.php?type=usermenu&user=" + username, true);
			httpObj.send(null);
			httpObj.onreadystatechange = function() {
				if ( (httpObj.readyState == 4) && (httpObj.status == 200) ) {
					document.getElementById("usermenubody").innerHTML = parseJSONforMenu(httpObj.responseText);
					if (keyPickerReady) {
						document.getElementById("keyPickerFromUserMenu").innerHTML = parseJSONforKeyPicker(httpObj.responseText);
						document.getElementById("keyPickerFromHotKeys").innerHTML = parseJSONforKeyPicker(httpObj2.responseText);
					}
					keyPickerReady = 1;
				}
			}
		}
	}
	httpObj2 = createXMLHttpRequest();
	if (httpObj2) {
		httpObj2.open("GET","getHaikuJSON.php?type=hotkeyslist",true);
		httpObj2.send(null);
		httpObj2.onreadystatechange = function() {
			if ( (httpObj2.readyState == 4) && (httpObj2.status == 200) ) {
				document.getElementById("hotkeyslistbody").innerHTML = parseJSONforMenu(httpObj2.responseText);
					if (keyPickerReady) {
						document.getElementById("keyPickerFromUserMenu").innerHTML = parseJSONforKeyPicker(httpObj.responseText);
						document.getElementById("keyPickerFromHotKeys").innerHTML = parseJSONforKeyPicker(httpObj2.responseText);
					}
					keyPickerReady = 1;
			}
		}
	}
};

function starIconMode() {

	/* 2重実行阻止 */
	var flag = document.getElementById('userIconMode').dataset.flag;
	if (flag == '1') {return;}
	document.getElementById('userIconMode').dataset.flag = '1';

	/* Star */
	var stars = document.getElementsByClassName('hatena-star-star');
	for (var i in stars) {
		if (i == 'length') {break;}
		var name = stars[i].alt;
		var color = 'yellow';
		if (color = /\ \(([a-zA-Z]+)\)$/.exec(name)) {
			color = color[1];
			name = name.replace(/\ \(([a-zA-Z]+)\)$/, '');
			stars[i].style.border = '2px solid ' + color;
		}
		stars[i].style.margin = '0 1px';
		stars[i].style.verticalAlign = 'middle';
		stars[i].src = 'http://www.st-hatena.com/users/' + name.slice(0, 2) + '/' + name + '/profile_s.gif';
	}

	/* Based on: http://coderepos.org/share/browser/lang/javascript/userscripts/hatena/hatena_replace_star_icon_anywhere.user.js */
	var show_name = Hatena.Star.Star.prototype.showName;
	var pushStars = Hatena.Star.Entry.prototype.pushStars;
	Hatena.Star.Star.prototype.showName = function(e) {
		this.screen_name = this.name;
		show_name.call(this, e);
	};
	Hatena.Star.Entry.prototype.pushStars = function(stars, color) {
		stars = stars.map(function(star) {
			var image = Hatena.User.getProfileIcon(star.name);
			image.alt = star.name;
			star.img	= image;
		if (color) image.style.border = '2px solid ' + color; 
			image.style.margin = '0 1px';
			return star;
		});
		pushStars.call(this, stars, color);
	};
	return;
}
