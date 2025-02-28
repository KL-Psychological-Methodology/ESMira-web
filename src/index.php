<?php
require_once 'backend/autoload.php';

use backend\Configs;
use backend\Main;

$lang = Main::getLang();

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
		if(isset($_GET['app_install'])) {
			$jsKey = "appInstall,id:$studyId";
		} else if(isset($_GET['from_url'])) {
			$fromUrl = $_GET['from_url'];
			$jsKey = "fallbackAppInstall,id:$studyId,fromUrl:$fromUrl";
		} else {
			$jsKey = "studyOverview,id:$studyId";
		}
		
		if(!isset($_GET['key']))
			$_GET['key'] = ''; //a saved cookie would override a study without access-key. Because of get_accessKey() this will overwrite the cookie as well
	}
	else if(isset($_GET['key']))
		$jsKey = isset($_GET['app_install']) ? 'appInstall' : 'studyOverview';
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
	$jsKey = 'initESMira';
}

$accessKey = Main::getAccessKey();
$servername = Configs::getServerName();
$serverVersion = Main::SERVER_VERSION;

$noJsUrl = "index_nojs.php?ref&$_SERVER[QUERY_STRING]";

if(isset($_GET['minimal']))
	$type = 'minimal ';
if(isset($_GET['grayscaleLight']))
	$type = 'grayscaleLight ';
else if(isset($_GET['grayscaleDark']))
	$type = 'grayscaleDark';
else
	$type = '';
?>


<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
	<meta charset="UTF-8">
	<title>ESMira</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="description" content="ESMira is a tool for running longitudinal studies (ESM, AA, EMA, ...) with data collection and communication with participants being completely anonymous." />
	<meta name="keywords" content="AA; ESM; EMA; Android; iOS; iPhone; Science; Questionnaire; Study; Mobile; Server; Open Source" />
</head>
<body onload="ESMira.init(<?php echo "'$jsKey','$servername',$serverVersion,'$accessKey','$lang','$type'"; ?>)">

<div id="header">
	<a href="#<?php echo $jsKey; ?>">
		<img src="frontend/imgs/webHeader.png" alt="ESMira"/>
	</a>
	<div class="title" id="headerServerName"></div>
</div>

<div id="sectionContainer">
	<div id="sectionsView">
		<div class="section" style="opacity: 1">
			<div class="sectionTitle"></div>
			<div class="sectionContent">
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
</div>


<input id="sectionBoxWidthSetter" type="range" min="20" max="100" value="45"/>

<div id="siteLangChooser"></div>
<a id="legalLink" href="#legal"></a>

</body>
</html>