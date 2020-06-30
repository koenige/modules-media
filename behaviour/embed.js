/*
 * zzform
 * JS for embedding
 * for media module
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/media
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function setCookie(cookieName, value, overwrite) {
	if (!overwrite) {
		var existingCookieValue = getCookie(cookieName);
		if (existingCookieValue) value = existingCookieValue + ',' + value;
	}
	var cookie = cookieName + "=" + encodeURIComponent(value);
	cookie += '; domain=%%% setting hostname %%%';
	cookie += '; max-age=' + 60*60*24*365;
	cookie += '; path=/; secure; samesite=strict';
	document.cookie = cookie;
}

function getCookie(cookieName){
   var i = 0;  // search position
   var search = cookieName + "=";
   while (i < document.cookie.length) {
      if (document.cookie.substring(i, i+search.length)==search) {
         var end = document.cookie.indexOf(";", i+search.length);
         end = (end>-1) ? end : document.cookie.length;
         var cookie = document.cookie.substring(i+search.length, end);
         return unescape(cookie);
      }
      i++;
   }
   return null
}

function setPrivacyCookie(type) {
	setCookie('privacy', type, false);
	setRedirect();
}

function setRedirect() {
	if (window.location.href.substring(window.location.href.length - 11) === '?inactive=1')
		window.location.href = window.location.href.substring(0, window.location.href.length - 11);
}

function checkPrivacyCookie(type) {
	var existingCookieValue = getCookie('privacy');
	if (existingCookieValue.split(',').includes(type)) setRedirect();
}

function changePrivacySettings() {
	var fieldset = document.getElementsByClassName('privacysettings');
	var settings = fieldset[0].getElementsByTagName('input');
	var value = '';
	for (i = 0; i < settings.length; i++) {
		if (settings[i].getAttribute('type') === 'checkbox') {
			if (settings[i].checked) value += ',' + settings[i].getAttribute('data-embed');
		}
	}
	if (value) {
		value = value.substring(1);
	}
	var button = document.getElementById('changePrivacySettings');
	button.value = button.getAttribute('data-set');
	button.setAttribute('disabled', true);
	setCookie('privacy', value, true);
}

function enablePrivacySettings() {
	var button = document.getElementById('changePrivacySettings');
	if (button.getAttribute('disabled', true)) {
		button.removeAttribute('disabled');
		button.value = button.getAttribute('data-default');
	}
}

function initPrivacySettings() {
	var fieldset = document.getElementsByClassName('privacysettings');
	var settings = fieldset[0].getElementsByTagName('input');

	for (i = 0; i < settings.length; i++) {
		settings[i].addEventListener('change', enablePrivacySettings);
	}
}

window.addEventListener('load', initPrivacySettings);
