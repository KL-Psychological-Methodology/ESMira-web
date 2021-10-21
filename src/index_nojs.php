<?php
ob_start();
require_once 'php/configs.php';
require_once 'php/basic_fu.php';
require_once 'php/permission_fu.php';
require_once 'php/string_fu.php';

$lang_name = get_lang('en');
$LANG = json_decode(file_get_contents("parts/locales/$lang_name.json"));


//
//Choose starting page:
//
if(file_exists(FOLDER_DATA)) {
	if(isset($_GET['app_install']))
		$page_url = 'php/no_js/pages/app_install.php';
	else if(isset($_GET['studies']))
		$page_url = 'php/no_js/pages/studies_list.php';
	else if(isset($_GET['about']))
		$page_url = 'php/no_js/pages/about.php';
	else if(isset($_GET['legal']))
		$page_url = 'php/no_js/pages/legal.php';
	else if(isset($_GET['change_lang']))
		$page_url = 'php/no_js/pages/change_lang.php';
	else if(isset($_GET['id']) || isset($_GET['qid']) || isset($_GET['key']))
		//we check in questionnaire_attend if we need to go to another page (informed_consent, get_participant, study_overview, ...)
		$page_url = 'php/no_js/pages/questionnaire_attend.php';
	else
		$page_url = 'php/no_js/pages/home.php';
}
else
	exit('Enable JavaScript to initialize');

if(!isset($_GET['key']))
	$_GET['key'] = ''; //a saved cookie would override a study without access-key. Because of get_accessKey() this will overwrite the cookie as well

$error = null;
function show_error($s) {
	global $error;
	$error = $s;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang_name; ?>">
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
	<meta name="keywords" content="AA; ESM; EMA; Android; iOS; iPhone; Science; Mobile; Server; Open Source" />
</head>
<body class="is_init">
	<div id="header">
		<a href="?">
			<img src="imgs/web_header.png" alt="ESMira"/>
		</a>
		<div class="title"><?php echo get_serverName(); ?></div>
	</div>
	<div id="no_js_info">
		<img class="middle" src="imgs/warn.svg" alt=""/>
		&nbsp;
		<span class="middle"><?php echo $LANG->no_js; ?></span>
	</div>
	
	
	<div id="el_pages">
		<div class="page has_title" style="opacity: 1">
			<?php require_once $page_url; ?>
		</div></div><!--Note: We cant have a whitespace here-->
	
	
	<?php
	if(isset($error)) {
		echo "<div id='errorEl'>$error</div>";
	}
	?>
	
	<div id="lang_chooser">
		<a href="?change_lang">
		<?php
		switch($lang_name) {
			case 'en':
				echo '&#127468;&#127463; English';
				break;
			case 'de':
				echo '&#127465;&#127466; Deutsch';
				break;
		}
		?>
		</a>
	</div>
	<a id="legalLink" class="internal_link no_arrow" href="?legal"><?php echo $LANG->impressum; ?></a>
</body>
</html>
<?php
ob_end_flush();
?>