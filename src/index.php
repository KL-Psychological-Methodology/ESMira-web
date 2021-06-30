<?php
require_once 'php/configs.php';
require_once 'php/files.php';
require_once 'php/basic_fu.php';
require_once 'php/permission_fu.php';
require_once 'php/string_fu.php';

$lang = get_lang();


//
//Choose starting page:
//
if(file_exists(FOLDER_DATA)) {
	
	if(isset($_GET['qid'])) {
		$questionnaire_index = (int)$_GET['qid'];
		$js_key = "attend,qId:$questionnaire_index";
		
		if(!isset($_GET['key']))
			$_GET['key'] = ''; //a saved cookie would override a study without access-key. Because of get_accessKey() this will overwrite the cookie as well
	}
	else if(isset($_GET['id'])) {
		$study_id = (int)$_GET['id'];
		$js_key = isset($_GET['app_install']) ? "appInstall,id:$study_id" : "sOverview,id:$study_id";
		
		if(!isset($_GET['key']))
			$_GET['key'] = ''; //a saved cookie would override a study without access-key. Because of get_accessKey() this will overwrite the cookie as well
	}
	else if(isset($_GET['key']))
		$js_key = isset($_GET['app_install']) ? 'appInstall' : 'sOverview';
	else if(isset($_GET['impressum'])) {
		$js_key = 'legal,impressum';
	}
	else if(isset($_GET['privacyPolicy']))
		$js_key = 'legal,privacyPolicy';
	else if(isset($_GET['about']))
		$js_key = 'about';
	else if(isset($_GET['studies']))
		$js_key = 'studies,attend';
	else if(isset($_GET['admin']))
		$js_key = 'admin';
	else
		$js_key = "home";
	
	require_once FILE_SERVER_SETTINGS;
	$serverSettings = SERVER_SETTINGS;
}
else {
	$js_key = 'init_esmira';
	require_once 'php/default_server_settings.php';
	$serverSettings = DEFAULT_SERVER_SETTINGS;
}
$access_key = get_accessKey();


$nojs_url = "index_nojs.php?ref&$_SERVER[QUERY_STRING]";

?>


<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
	<meta charset="UTF-8">
	<title>ESMira</title>
	
	<script type="text/javascript"><?php
		//TODO: loading Lang here and serve it down to the Javascript modules as a global saves us a request but is kinda ugly. Ideas?
		
		if(isset($_GET['minimal']))
			$type = 'minimal ';
		if(isset($_GET['grayscaleLight']))
			$type = 'grayscaleLight ';
		else if(isset($_GET['grayscaleDark']))
			$type = 'grayscaleDark';
		else
			$type = '';
		$servername = $serverSettings['serverName'];
		$serverVersion = SERVER_VERSION;
		echo "let a='$js_key',b='$servername',c=$serverVersion,d='$access_key',e='$lang',f='$type',g=".file_get_contents("parts/locales/$lang.json"); ?></script>
	
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body onload="ESMira.init(a, b, c, d, e, f, g)">

<div id="header">
	<a href="#<?php echo $js_key; ?>">
		<img src="imgs/web_header.png" alt="ESMira"/>
	</a>
	<div class="title" id="header_serverName"></div>
</div>


<div id="el_pages">
	<div class="page" style="opacity: 1">
		<div class="page_title"></div>
		<div class="page_content">
			<noscript>
				<div class="center highlight">
					No JavaScript detected. if you are not redirected automatically, click
					<a href="<?php echo $nojs_url; ?>">here</a>
				</div>
				<meta http-equiv="refresh" content="0; url=<?php echo $nojs_url; ?>"/>
			</noscript>
		</div>
	</div>
</div>


<div id="btn_up"></div>

<div id="current_stateInfo_el">
	<div id="stateInfo_positioner">
		<div id="titleBox_cell">
			<div id="titleBox_absolute">
				<div id="titleBox">
					<div id="nav_menu">
						<div id="nav_content"></div>
					</div>
				</div>
			</div>
			<div id="titleBox_shadow"></div>
		</div>
		<div id="saveBox" class="highlight clickable"></div>
		
		<div id="publishBox" class="clickable">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M5 4v2h14V4H5zm0 10h4v6h6v-6h4l-7-7-7 7z"/></svg>
		</div>
	</div>
</div>

<input id="pageBox_width" type="range" min="20" max="100" value="45">

<div id="lang_chooser"></div>
<a id="legalLink" class="internal_link no_arrow" href="#legal"></a>

</body>
</html>