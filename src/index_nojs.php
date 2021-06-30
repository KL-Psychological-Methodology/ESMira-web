<?php
ob_start();
require_once 'php/configs.php';
require_once 'php/basic_fu.php';
require_once 'php/permission_fu.php';
require_once 'php/string_fu.php';

$lang_name = get_lang();
$LANG = json_decode(file_get_contents("parts/locales/$lang_name.json"));

$server_name = file_get_contents(FILE_SERVER_NAME);
$page_key = 'home';


//
//Choose starting page:
//
if(file_exists(FOLDER_DATA)) {
	if(isset($_GET['app_install']))
		$page_key = 'app_install';
	else if(isset($_GET['studies']))
		$page_key = 'studies';
	else if(isset($_GET['about']))
		$page_key = 'about';
	else if(isset($_GET['impressum']))
		$page_key = 'impressum';
	else if(isset($_GET['privacyPolicy']))
		$page_key = 'privacyPolicy';
	else if(isset($_GET['change_lang']))
		$page_key = 'change_lang';
	else if(isset($_GET['id']) || isset($_GET['qid']) || isset($_GET['key']))
		$page_key = 'questionnaire_attend'; //we check in questionnaire_attend if we need to go to another page (informed_consent, get_participant, study_overview, ...)
}

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
</head>
<body class="is_init">
	<div id="header">
		<a href="?">
			<img src="imgs/web_header.png" alt="ESMira"/>
		</a>
		<div class="title"><?php echo $server_name; ?></div>
	</div>
	<div id="no_js_info">
		<img class="middle" src="imgs/warn.svg" alt=""/>
		&nbsp;
		<span class="middle"><?php echo $LANG->no_js; ?></span>
	</div>
	
	
	<div id="el_pages">
		<div class="page has_title" style="opacity: 1">
			<?php
			$PAGES = [
					'home' => 'php/no_js/pages/home.php',
					'about' => 'php/no_js/pages/about.php',
					'app_install' => 'php/no_js/pages/app_install.php',
					'change_lang' => 'php/no_js/pages/change_lang.php',
					'impressum' => 'php/no_js/pages/impressum.php',
					'privacyPolicy' => 'php/no_js/pages/privacy_policy.php',
					'questionnaire_attend' => 'php/no_js/pages/questionnaire_attend.php',
					'studies' => 'php/no_js/pages/studies_list.php',
					'study_overview' => 'php/no_js/pages/study_overview.php'
			];
			if(isset($PAGES[$page_key]))
				require $PAGES[$page_key];
			else
				require $PAGES['home'];
			?>
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
	
	<?php
	if(file_exists(FILE_IMPRESSUM))
		echo '<a id="legalLink" class="internal_link no_arrow" href="?impressum" class="no_arrow">'.$LANG->impressum.'</a>';
	else if(file_exists(FILE_PRIVACY_POLICY))
		echo '<a id="legalLink" class="internal_link no_arrow" href="?privacyPolicy" class="no_arrow">'.$LANG->privacyPolicy.'</a>';
	?>
</body>
</html>
<?php
ob_end_flush();
?>