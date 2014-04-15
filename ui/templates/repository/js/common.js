
function setCookie(cookieName,cookieValue,nDays) {
	var today = new Date();
	var expire = new Date();
	if (nDays==null || nDays==0) nDays=1;
	expire.setTime(today.getTime() + 3600000*24*nDays);
	document.cookie = cookieName+"="+escape(cookieValue)
		+ ";expires="+expire.toGMTString();
}

function getCookie(name) {
	var parts = document.cookie.split(name + "=");
	if (parts.length == 2) return parts.pop().split(";").shift();
}

