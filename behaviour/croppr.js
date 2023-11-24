/*
 * zzform
 * JS for cropping
 * for media module
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


var cropButtonActivate = document.getElementById('cropimage_activate');
var cropButtonSave = document.getElementById('cropimage_save');
var croppr;
cropButtonSave.style.display = 'none';
cropButtonActivate.addEventListener('click', function() {
	cropButtonSave.style.display = 'inline-block';
	this.style.display = 'none';
	croppr = new Croppr('#cropper', {
		aspectRatio: 1
	});
});
cropButtonSave.addEventListener('click', function() {
	cropButtonActivate.style.display = 'inline-block';
	this.style.display = 'none';
	saveCrop(this.dataset.medium_id);
	croppr.destroy();
});

var xhr = null;

getXmlHttpRequestObject = function() {
	if (!xhr) {
		xhr = new XMLHttpRequest();
	}
	return xhr;
};

function saveCrop(id) {
	xhr = getXmlHttpRequestObject();
	xhr.onreadystatechange = cropEventHandler;
	xhr.open('POST', window.location.href, true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var returnVal = croppr.getValue('ratio');
	xhr.send('httpRequest=crop&medium_id=' + id + '&x=' + returnVal.x + '&y=' + returnVal.y + '&width=' + returnVal.width + '&height=' + returnVal.height);
};

function cropEventHandler() {
	if (xhr.readyState == 4 && xhr.status == 200) {
		alert('%%% text Medium was cropped. %%%');
	}
}
