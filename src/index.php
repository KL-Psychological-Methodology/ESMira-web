<?php
require_once 'backend/autoload.php';

use backend\Configs;
use backend\Main;

$lang = Main::getLang('en');

//
//Choose starting page:
//
if(Configs::getDataStore()->isInit()) {
	if(isset($_GET['qid'])) {
		$questionnaireId = (int)$_GET['qid'];
		$jsKey = "attend,qId:$questionnaireId";
		
		if(!isset($_GET['key']))
			$_GET['key'] = ''; //a saved cookie would override a study without access-key. Because of get_accessKey() this will overwrite the cookie as well
	}
	else if(isset($_GET['id'])) {
		$studyId = (int)$_GET['id'];
		$jsKey = isset($_GET['app_install']) ? "appInstall,id:$studyId" : "sOverview,id:$studyId";
		
		if(!isset($_GET['key']))
			$_GET['key'] = ''; //a saved cookie would override a study without access-key. Because of get_accessKey() this will overwrite the cookie as well
	}
	else if(isset($_GET['key']))
		$jsKey = isset($_GET['app_install']) ? 'appInstall' : 'sOverview';
	else if(isset($_GET['impressum']))
		$jsKey = 'legal,impressum';
	else if(isset($_GET['privacyPolicy']))
		$jsKey = 'legal,privacyPolicy';
	else if(isset($_GET['about']))
		$jsKey = 'about';
	else if(isset($_GET['studies']))
		$jsKey = 'studies,attend';
	else if(isset($_GET['admin']))
		$jsKey = 'admin';
	else
		$jsKey = "home";
}
else {
	$servername = '';
	$jsKey = 'init_esmira';
}

$accessKey = Main::getAccessKey();
$servername = Configs::getServerName();

$noJsUrl = "index_nojs.php?ref&$_SERVER[QUERY_STRING]";
?>


<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
	<meta charset="UTF-8">
	<title>ESMira</title>
	
	<script type="text/javascript"><?php
		//TODO: loading Lang here and serve it down to the Javascript modules saves us a request. But since it will be cached anyway, we should load it from javascript
		
		if(isset($_GET['minimal']))
			$type = 'minimal ';
		if(isset($_GET['grayscaleLight']))
			$type = 'grayscaleLight ';
		else if(isset($_GET['grayscaleDark']))
			$type = 'grayscaleDark';
		else
			$type = '';
		$serverVersion = Main::SERVER_VERSION;
		echo "let a='$jsKey',b='$servername',c=$serverVersion,d='$accessKey',e='$lang',f='$type',g=".file_get_contents("locales/$lang.json"); ?>
	</script>
	
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="description" content="ESMira is a tool for running longitudinal studies (ESM, AA, EMA, ...) with data collection and communication with participants being completely anonymous." />
	<meta name="keywords" content="AA; ESM; EMA; Android; iOS; iPhone; Science; Mobile; Server; Open Source" />
</head>
<body onload="ESMira.init(a, b, c, d, e, f, g)">

<div id="header">
	<a href="#<?php echo $jsKey; ?>">
		<img src="frontend/imgs/web_header.png" alt="ESMira"/>
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
					<a href="<?php echo $noJsUrl; ?>">here</a>
				</div>
				<meta http-equiv="refresh" content="0; url=<?php echo $noJsUrl; ?>"/>
			</noscript>
		</div>
	</div>
</div>


<div id="btn_up"></div>


<input id="pageBox_width" type="range" min="20" max="100" value="45">

<div id="lang_chooser"></div>
<a id="legalLink" class="internal_link no_arrow" href="#legal"></a>

</body>
</html>