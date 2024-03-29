<?php

require_once 'backend/autoload.php';

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\Page;
use backend\noJs\pages\About;
use backend\noJs\pages\AppInstall;
use backend\noJs\pages\ChangeLang;
use backend\noJs\pages\Home;
use backend\noJs\pages\Legal;
use backend\noJs\pages\QuestionnaireAttend;
use backend\noJs\pages\StudiesList;
use backend\exceptions\PageFlowException;

ob_start();


//
//Choose starting page:
//

if(!isset($_GET['key']))
	$_GET['key'] = ''; //a saved cookie would override a study without access-key. Because of get_accessKey() this will overwrite the cookie as well


/**
 * @throws ForwardingException
 * @throws PageFlowException
 * @throws CriticalException
 */
function getPageObj(): Page {
	if(isset($_GET['app_install']))
		return new AppInstall();
	else if(isset($_GET['studies']))
		return new StudiesList();
	else if(isset($_GET['about']))
		return new About();
	else if(isset($_GET['legal']))
		return new Legal();
	else if(isset($_GET['change_lang']))
		return new ChangeLang();
	else if(isset($_GET['id']) || isset($_GET['qid']) || (isset($_GET['key']) && $_GET['key']))
		//we check in questionnaire_attend if we need to go to another page (informed_consent, get_participant, study_overview, ...)
		return new QuestionnaireAttend();
	else
		return new Home();
}
/**
 * @throws ForwardingException
 * @throws PageFlowException
 * @throws CriticalException
 */
function pageToOutput(Page $page) {
	echo '<div class="sectionTop"><div class="sectionTitle">'
			.$page->getTitle()
		.'</div></div>'
		.'<div class="sectionContent">' .$page->getContent() .'</div>';
}

function drawPage(Page $page = null) {
	if(!Configs::getDataStore()->isInit())
		echo "<div id=\"errorEl\">Enable JavaScript to initialize</div>";
	
	try {
		if($page == null)
			$page = getPageObj();
		
		pageToOutput($page);
	}
	catch(ForwardingException $exception) {
		drawPage($exception->getPage());
	}
	catch(CriticalException $exception) {
		echo "<div id=\"errorEl\">$exception</div>";
	}
	catch(PageFlowException $exception) {
		echo "<div id=\"errorEl\">$exception</div>";
	}
	catch(Throwable $exception) {
		echo "<div id=\"errorEl\">Internal server error!</div>";
	}
}

?>

<!DOCTYPE html>
<html lang="<?php echo Main::getLang(); ?>">
<head>
	<meta charset="UTF-8">
	<title>ESMira</title>
	
	<style>
        #errorEl {
            position:fixed;
            left:0;
            right:0;
            bottom:0;
            background-color: #dc4e9d;
            z-index:101;
            padding:5px;
			
			color: white;
			text-align: center;
			min-height: 35px;
        }
	</style>
	<meta name="description" content="ESMira is a tool for running longitudinal studies (ESM, AA, EMA, ...) with data collection and communication with participants being completely anonymous." />
	<meta name="keywords" content="AA; ESM; EMA; Android; iOS; iPhone; Science; Questionnaire; Study; Mobile; Server; Open Source" />
</head>
<body class="isInit">
	<div id="header">
		<a href="?">
			<img src="frontend/imgs/webHeader.png" alt="ESMira"/>
		</a>
		<div class="title"><?php echo Configs::getServerName(); ?></div>
	</div>
	<div id="no_js_info">
		<img class="middle" src="frontend/imgs/icons/warn.svg" alt=""/>
		&nbsp;
		<span class="middle"><?php echo Lang::get('no_js'); ?></span>
	</div>
	
	
	<div id="sectionsView">
		<div class="section" style="opacity: 1">
			<?php drawPage(); ?>
		</div></div><!--Note: We cant have a whitespace here-->
	
	
	
	<div id="siteLangChooser">
		<a href="?change_lang">
		<?php
		switch(Main::getLang()) {
			case 'de':
				echo 'Deutsch';
				break;
			case 'uk':
				echo 'українська';
				break;
			case 'en':
			default:
				echo 'English';
				break;
		}
		?>
		</a>
	</div>
	<a id="legalLink" href="?legal"><?php echo Lang::get('impressum'); ?></a>
</body>
</html>
<?php
ob_end_flush();
?>