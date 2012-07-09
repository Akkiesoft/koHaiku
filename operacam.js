var video = document.getElementsByTagName('video')[0], 
       heading = document.getElementsByTagName('h1')[0];

if(navigator.getUserMedia) {
  navigator.getUserMedia('video', successCallback, errorCallback);
  function successCallback( stream ) {
    video.src = stream;
  }
  function errorCallback( error ) {
    heading.textContent = 
        "An error occurred: [CODE " + error.code + "]";
  }
} else {
  heading.textContent = 
      "Native web camera streaming is not supported in this browser!";
}


function cheese() {
	var camsound = document.getElementById('camsound');
	camsound.play();

	var video = document.getElementById('camwindow');
	var preview = document.getElementById('campreviewimage');
	var canvas = document.getElementById('camcanvas');
	var cheese = document.getElementById('camcheese');
	var save = document.getElementById('camsave');
	var context = canvas.getContext('2d');

	canvas.width = video.videoWidth;
	canvas.height = video.videoHeight;
	context.drawImage(video,0,0,video.videoWidth,video.videoHeight);

	video.src = '';
	video.style.display='none';
	preview.style.display='block';
	cheese.style.display='none';
	save.style.display='block';
	preview.src=canvas.toDataURL("image/jpeg");
}

function save() {
	var canvas = document.getElementById('camcanvas');
	document.haikuForm.camdata.value=canvas.toDataURL("image/jpeg");
	popupCam();
}

function startCam() {
	var video = document.getElementById('camwindow');
	var preview = document.getElementById('campreviewimage');
	var cheese = document.getElementById('camcheese');
	var save = document.getElementById('camsave');
	video.style.display='block';
	preview.style.display='none';
	cheese.style.display='block';
	save.style.display='none';

	if(navigator.getUserMedia) {
		navigator.getUserMedia('video', successCallback, errorCallback);
		function successCallback( stream ) {
			video.src = stream;
		}
		function errorCallback( error ) {
			preview.textContent = 
			"Ç¶ÇÁÅ[: [CODE " + error.code + "]";
		}
	} else {
		preview.textContent = 
			"ç≈êVÇÃOperaÇ≈Ç®ÇΩÇÃÇµÇ›Ç≠ÇæÇ≥Ç¢ÅB";
	}
}

function popupCam() {
	var popupCam = document.getElementById('popupCam').style;
	var popupCamStat = document.getElementById('popupCamStat');

	if (popupCamStat.className != 'none') {
		popupCam.display = 'none';
		popupCamStat.className = 'none';
		video.src = '';
		return;
	}
	if (popupCam.display == 'none') {
		popupCam.display = 'block';
		popupCam.height = getPageSize()[1] + 'px';
		popupCamStat.className = 'enabled';
		startCam();
	}
}