<?php
function getOperacamBoxHTML() {
	return <<<EOM
<div id="popupCam" class="keyPicker" style="display:none;">
	<div class="keyPickerWrapper">
		<div class="keyPickerBody">
			<video autoplay id="camwindow"></video>
			<div id="campreview"></div>
			<img src="dummy.gif" id="campreviewimage" style="display:none;">
			<div id="camcheese"><input type="button" onclick="cheese()" value="さつえい" style="width:100%;height:30px;"></div>
			<div id="camsave" style="display:none;">
				<input type="button" onclick="save()" value="けってい" style="width:100%;height:30px;"><br><br>
				<input type="button" onclick="startCam()" value="もっかい" style="width:100%;height:30px;">
			</div>
			<div style="display:none;">
				<canvas id="camcanvas"></canvas>
				<audio src="cheese.ogg" preload="auto" id="camsound"></audio>
			</div>
		</div>
		<div id="close"><a href="javascript:void(0);" onclick="popupCam()">とじる</a></div>
	</div>
</div>
<span id="popupCamStat" class="none"></span>
EOM;
}

?>