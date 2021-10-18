<?php
require_once 'php/basic_fu.php';
require_once 'php/no_js/nojs_fu.php';
require_once 'php/no_js/inputs.php';

function participant_isValid($participant) {
	return strlen($participant) >= 1 && preg_match('/^[a-zA-Z0-9À-ž_\s\-()]+$/', $participant);
}

$studyData = get_studyData();
if(isset($studyData['notFound'])) {
	if(isset($studyData['error']))
		show_error($studyData['error']);
	return require 'studies_list.php';
}
$study = $studyData['study'];
$study_id = $study->id;

if(isset($study->publishedWeb) && !$study->publishedWeb)
	return require 'pages/app_install.php';

if(!$studyData['questionnaire'])
	return require 'study_overview.php';

$questionnaire = $studyData['questionnaire'];




if(isset($_POST['delete_participant'])) {
	save_dataset(DATASET_TYPE_QUIT, $_POST['participant'], $study);
	delete_cookie('participant'.$study_id);
	remove_postHeader();
	return;
}
else if(isset($study->informedConsentForm) && strlen($study->informedConsentForm) && !isset($_COOKIE["informed_consent$study_id"])) {
	if(!isset($_POST['informed_consent']))
		return require 'informed_consent.php';
	
	else
		create_cookie("informed_consent$study_id", '1', 32532447600);
}

if(!isset($_COOKIE["participant$study_id"]) || !participant_isValid($_COOKIE["participant$study_id"])) {
	if(!isset($_POST['participant']) || !participant_isValid($_POST['participant']))
		return require 'get_participant.php';
	else {
		$participant = $_POST['participant'];
		if(isset($_POST['new_participant']))
			save_dataset(DATASET_TYPE_JOINED, $participant, $study);
		
		create_cookie("participant$study_id", $participant, 32532447600);
		
	}
}
else {
	$participant = $_COOKIE["participant$study_id"];
}




$pages = $questionnaire->pages;

//used only in dynamicInput:
$last_completed_cookie_name = sprintf(COOKIE_LAST_COMPLETED, $study_id, $questionnaire->internalId);
$last_completed = (isset($_COOKIE[$last_completed_cookie_name])) ? $_COOKIE[$last_completed_cookie_name] : 0;

$form_started = isset($_POST['form_started']) && (int)$_POST['form_started'] ? (int)$_POST['form_started'] : get_milliseconds();

//$other_dataInputs = "<input type=\"hidden\" name=\"participant\" value=\"$participant\"/>
//	<input type=\"hidden\" name=\"informed_consent\" value=\"1\"/>
//	<input type=\"hidden\" name=\"form_started\" value=\"$form_started\"/>";

$cache = isset($_POST['cached_values']) ? $_POST['cached_values'] : []; //TODO: save and load data from response_cache cookie



//if data was sent, we check it now so we can show the correct page number in title:

$page_before = isset($_POST['page']) ? (int)$_POST['page'] : 0;
if(isset($_POST) && (isset($_POST['save']) || isset($_POST['continue']))) {
	if(!isset($pages[$page_before]))
		return;
	$inputs_before = $pages[$page_before]->inputs;
	$is_save = isset($_POST['save']);
	$data_missing = false;
	
	$responses = isset($_POST['responses']) ? $_POST['responses'][$page_before] : [];
	$concat = isset($_POST['concat_values']) ? $_POST['concat_values'][$page_before] : [];
	
	foreach($inputs_before as $i => $input) {
		$input = $inputs_before[$i];
		$value = isset($responses[$i]) ? $responses[$i] : '';
		
		if(isset($input->required) && $input->required && $value == '') {
			$data_missing = true;
			break;
		}
		else {
			if(isset($input->responseType)) {
				switch($input->responseType) {
					case 'dynamic_input':
						if(isset($concat[$i]))
							$value = $concat[$i].'/'.$value;
						break;
					case 'time':
						if(isset($input->forceInt) && $input->forceInt) {
							$split = explode(':', $value);
							if(sizeof($split) == 2)
								$value = $split[0] * 60 + $split[1];
						}
						break;
				}
			}
			
			$cache[$input->name] = $value;
			
			//we will call save_dataset() in if($is_save) {
		}
	}
	
	
	if($is_save) {
		if($data_missing) {
			$pageInt = $page_before;
			$success_saving = false;
		}
		else {
			//used in datasets.php:
//			$_POST['responses']['formDuration'] = get_milliseconds() - $form_started;
			$cache['formDuration'] = get_milliseconds() - $form_started;
			
			$success_saving = save_dataset(DATASET_TYPE_QUESTIONNAIRE, $participant, $study, $questionnaire, $cache);
			
			if($success_saving) {
				create_cookie($last_completed_cookie_name, time(), 32532447600);
				$pageInt = $page_before;
			}
			else
				$pageInt = $page_before;
		}
	}
	else {
		$pageInt = $data_missing ? $page_before : $page_before + 1;
		$success_saving = false;
	}
}
else {
	$pageInt = 0;
	$data_missing = false;
	$success_saving = false;
}


if($pageInt >= sizeof($pages)) {
	show_error($LANG->error_unknown_data);
	return;
}
$is_last_page = $pageInt + 1 == sizeof($pages);

$page = $pages[$pageInt];
$inputs = $page->inputs;


?>
<div class="page_top page_title">
	<?php
	echo isset($questionnaire->title) ? $questionnaire->title : $LANG->questionnaire;
	if(sizeof($pages) > 1)
		echo ' (' .($pageInt+1).'/'.sizeof($pages).')';
	?>
</div>
<div class="page_content">
	<?php
	if(!questionnaire_isActive($questionnaire)) {
		echo '<p class="highlight center">' .$LANG->error_questionnaire_not_active .'</p>';
		return;
	}
	if($success_saving) {
		echo '<p class="center">';
		
		if(isset($study->webQuestionnaireCompletedInstructions) && strlen($study->webQuestionnaireCompletedInstructions))
			echo $study->webQuestionnaireCompletedInstructions;
		else
			echo $LANG->default_webQuestionnaireCompletedInstructions;
		
		echo '</p></div>';
		return;
	}
	else if($data_missing) {
		echo '<p class="highlight center">' .$LANG->error_missing_requiredField .'</p>';
	}
	?>
	
	<form id="participant_box" class="small_text" action="" method="post">
		<span class="highlight" data-bind="text: Lang.colon_user_id"><?php echo $LANG->user_id; ?>:</span>
		<span><?php echo $participant; ?></span>
		<input type="hidden" name="participant" value="<?php echo $participant; ?>"/>
		<input type="submit" name="delete_participant" value="<?php echo $LANG->change; ?>"/>
	</form>
	<hr/>
	
	<form class="colored_lines" id="questionnaire_box" method="post">
		<?php
		
		$inputObj = new Inputs($study_id, $last_completed, $pageInt, $cache);
		
		if(isset($page->randomized) && $page->randomized) {
			foreach($inputs as $i => $input) {
				$input->phpIndex = $i;
			}
			shuffle($inputs);
			
			foreach($inputs as $input) {
				$inputObj->draw_input($input, $input->phpIndex);
			}
		}
		else {
			foreach($inputs as $i => $input) {
				$inputObj->draw_input($input, $i);
			}
		}
		
		
		echo "<input type=\"hidden\" name=\"participant\" value=\"$participant\"/>
		<input type=\"hidden\" name=\"informed_consent\" value=\"1\"/>
		<input type=\"hidden\" name=\"form_started\" value=\"$form_started\"/>";
		
		
		foreach($cache as $key => $value) {
			echo "<input type=\"hidden\" name=\"cached_values[$key]\" value=\"$value\"/>";
		}
		?>
		
		
		<p class="small_text spacing_top">* input required</p>
		<?php
		
		echo $is_last_page
			? '<input type="submit" name="save" class="right" value="' .$LANG->save.'"/>'
			: '<input type="submit" name="continue" class="right" value="'.$LANG->continue.'"/>';
		?>
	
		<input type="hidden" name="page" value="<?php echo $pageInt; ?>"/>
	</form>
</div>