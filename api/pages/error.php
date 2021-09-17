<?php
$GLOBALS['organizrPages'][] = 'error';
function get_page_error($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	if ((!$Organizr->hasDB())) {
		return false;
	}
	$nonRoot = isset($_GET['organizr']);
	$nonRootPath = ($nonRoot) ? $Organizr->getRootPath() : '';
	$error = $_GET['vars']['var1'] ?? 404;
	$errorDetails = $Organizr->errorCodes($error);
	$redirect = $_GET['vars']['var2'] ?? null;
	if ($redirect) {
		$Organizr->debug($redirect);
	}
	$GLOBALS['responseCode'] = 200;
	return '
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<meta content="' . $Organizr->config['description'] . '" name="description">
	<meta content="CauseFX" name="author">
	' . $Organizr->favIcons($nonRootPath) . '
	<title>Error ' . $Organizr->config['title'] . '</title>
	' . $Organizr->loadResources(
			[
				'bootstrap/dist/css/bootstrap.min.css',
				'css/animate.css',
				'plugins/bower_components/overlayScrollbars/OverlayScrollbars.min.css',
				'css/dark.min.css',
				'css/organizr.min.css',
				'js/jquery-2.2.4.min.js',
				'js/jquery-lang.min.js'
			], $nonRootPath
		) . '
	' . $Organizr->setTheme(null, $nonRootPath) . '
	<style id="user-appearance"></style>
	<style id="custom-theme-css"></style>
	<style id="custom-css"></style>
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"
			integrity="sha384-0s5Pv64cNZJieYFkXYOTId2HMA2Lfb6q2nAcx2n0RTLUnCAoTTsS0nKEO27XyKcY"
			crossorigin="anonymous"></script>
	<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"
			integrity="sha384-ZoaMbDF+4LeFxg6WdScQ9nnR1QC2MIRxA1O9KWEXQwns1G8UNyIEZIQidzb0T1fo"
			crossorigin="anonymous"></script>
	<![endif]-->
</head>
<body class="fix-header">
<!-- ============================================================== -->
<!-- Preloader -->
<!-- ==============================================================
<div id="preloader" class="preloader">
	<svg class="circular" viewbox="25 25 50 50">
		<circle class="path" cx="50" cy="50" fill="none" r="20" stroke-miterlimit="10" stroke-width="10"></circle>
	</svg>
</div>-->
<!-- ============================================================== -->
<!-- Wrapper -->
<!-- ============================================================== -->
<section id="wrapper">
	<div class="error-box">
		<div class="error-body text-center">
			<h1 class="text-danger">' . $error . '</h1>
			<h2 class="text-uppercase" lang="en">' . $errorDetails['type'] . '</h2>
			<h3 class="text-uppercase" lang="en">' . $errorDetails['description'] . '</h3>
			<p class="text-muted m-t-30 m-b-30">Hey there, ' . $Organizr->user['username'] . '.  Looks like you tried accessing something that just ain\'t right!  WTF right?! </p>
			<a href="' . $nonRootPath . '" class="btn btn-danger btn-rounded waves-effect waves-light m-b-40">Back Home</a>
		</div>
	</div>
</section>
<script>
languageList = ' . $Organizr->languagePacks(true) . '
var langStrings = { "token": {} };
var lang = new Lang();
loadLanguageList();
lang.init({
	currentLang: (getCookie("organizrLanguage")) ? getCookie("organizrLanguage") : "en",
	cookie: {
		name: "organizrLanguage",
		expiry: 365,
		path: "/"
	},
	allowCookieOverride: true
});

$.urlParam = function(name){
	let results = new RegExp("[\?&]" + name + "=([^&#]*)").exec(window.location.href);
	if (results == null) {
		return null;
	} else {
		return decodeURI(results[1]) || 0;
	}
};
if ($.urlParam("return") !== null && "' . $Organizr->user['groupID'] . '" === "999") {
	local("set", "uri", $.urlParam("return"));
}
function localStorageSupport() {
	return (("localStorage" in window) && window["localStorage"] !== null)
}
function local(type,key,value=null){
	if (localStorageSupport) {
		switch (type) {
			case "set":
			case "s":
				localStorage.setItem(key,value);
				break;
			case "get":
			case "g":
				return localStorage.getItem(key);
				break;
			case "remove":
			case "r":
				localStorage.removeItem(key);
				break;
		}
	}
}
function loadLanguageList(){
	$.each(languageList, function(i,v) {
		lang.dynamic(v.code, "' . $nonRootPath . 'js/langpack/"+v.filename);
	});
}
function getCookie(cname) {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(";");
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == " ") {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}
</script>
</body>
</html>
';
}