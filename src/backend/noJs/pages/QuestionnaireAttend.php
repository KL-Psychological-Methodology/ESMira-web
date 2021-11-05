<?php

namespace backend\noJs\pages;

use backend\CreateDataSet;
use Exception;
use backend\Base;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\Extra;
use backend\noJs\Inputs;
use backend\noJs\Page;

class QuestionnaireAttend implements Page {
	const COOKIE_LAST_COMPLETED = 'last_completed%1$d_%2$d';
	
	private $study;
	private $pageInt;
	private $form_started;
	private $questionnaire;
	private $success_saving = false;
	private $data_missing = false;
	private $participant;
	private $dataCache;
	
	/**
	 * @throws ForwardingException
	 * @throws Exception
	 */
	public function __construct() {
		$studyData = Extra::get_studyData();
		if(isset($studyData['notFound'])) {
			if(isset($studyData['error']))
				throw new Exception($studyData['error']);
			throw new ForwardingException(new StudiesList());
		}
		$this->study = $studyData['study'];
		$study_id = $this->study->id;
		
		if(isset($this->study->publishedWeb) && !$this->study->publishedWeb)
			throw new ForwardingException(new AppInstall());
		
		if(!$studyData['questionnaire'])
			throw new ForwardingException(new StudyOverview());
		
		$this->questionnaire = $studyData['questionnaire'];
		
		
		
		
		if(isset($_POST['delete_participant'])) {
			Extra::save_dataset(CreateDataSet::DATASET_TYPE_QUIT, $_POST['participant'], $this->study);
			Base::delete_cookie('participant'.$study_id);
			Extra::remove_postHeader();
			return;
		}
		else if(isset($this->study->informedConsentForm) && strlen($this->study->informedConsentForm) && !isset($_COOKIE["informed_consent$study_id"])) {
			if(!isset($_POST['informed_consent']))
				throw new ForwardingException(new InformedConsent());
			else
				Base::create_cookie("informed_consent$study_id", '1', 32532447600);
		}
		
		if(!isset($_COOKIE["participant$study_id"]) || !self::participant_isValid($_COOKIE["participant$study_id"])) {
			if(!isset($_POST['participant']) || !self::participant_isValid($_POST['participant']))
				throw new ForwardingException(new GetParticipant());
			else {
				$this->participant = $_POST['participant'];
				if(isset($_POST['new_participant']))
					Extra::save_dataset(CreateDataSet::DATASET_TYPE_JOINED, $this->participant, $this->study);
				
				Base::create_cookie("participant$study_id", $this->participant, 32532447600);
			}
		}
		else
			$this->participant = $_COOKIE["participant$study_id"];
		
		
		$pages = $this->questionnaire->pages;
		
		$this->form_started = isset($_POST['form_started']) && (int)$_POST['form_started'] ? (int)$_POST['form_started'] : Base::get_milliseconds();
		
		$this->dataCache = isset($_POST['cached_values']) ? $_POST['cached_values'] : []; //TODO: save and load data from response_cache cookie
		
		
		//if data was sent, we check it now so we can show the correct page number in title:
		
		$page_before = isset($_POST['page']) ? (int)$_POST['page'] : 0;
		if(isset($_POST) && (isset($_POST['save']) || isset($_POST['continue']))) {
			if(!isset($pages[$page_before]))
				throw new Exception(Lang::get('error_unknown_data'));
			
			$inputs_before = $pages[$page_before]->inputs;
			$is_save = isset($_POST['save']);
			
			$responses = isset($_POST['responses']) ? $_POST['responses'][$page_before] : [];
			$concat = isset($_POST['concat_values']) ? $_POST['concat_values'][$page_before] : [];
			
			foreach($inputs_before as $i => $input) {
				$input = $inputs_before[$i];
				$value = isset($responses[$i]) ? $responses[$i] : '';
				
				if(isset($input->required) && $input->required && $value == '') {
					$this->data_missing = true;
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
					
					$this->dataCache[$input->name] = $value;
					
					//we will call save_dataset() in if($is_save) {
				}
			}
			
			
			if($is_save) {
				if($this->data_missing) {
					$this->pageInt = $page_before;
				}
				else {
					//used in CreateDataSet:
					$this->dataCache['formDuration'] = Base::get_milliseconds() - $this->form_started;
					
					$this->success_saving = Extra::save_dataset(CreateDataSet::DATASET_TYPE_QUESTIONNAIRE, $this->participant, $this->study, $this->questionnaire, $this->dataCache);
					
					if($this->success_saving) {
						$last_completed_cookie_name = sprintf(self::COOKIE_LAST_COMPLETED, $this->study->id, $this->questionnaire->internalId);
						Base::create_cookie($last_completed_cookie_name, time(), 32532447600);
						$this->pageInt = $page_before;
					}
					else
						$this->pageInt = $page_before;
				}
			}
			else {
				$this->pageInt = $this->data_missing ? $page_before : $page_before + 1;
			}
		}
		else
			$this->pageInt = 0;
		
		
		
		if($this->pageInt >= sizeof($pages))
			throw new Exception(Lang::get('error_unknown_data'));
	}
	
	
	static function participant_isValid($participant) {
		return strlen($participant) >= 1 && preg_match('/^[a-zA-Z0-9À-ž_\s\-()]+$/', $participant);
	}
	
	public function getTitle() {
		$pagesSize = sizeof($this->questionnaire->pages);
		$output = isset($this->questionnaire->title) ? $this->questionnaire->title : Lang::get('questionnaire');
		if($pagesSize > 1)
			$output .= ' (' .($this->pageInt+1).'/'.$pagesSize.')';
		return $output;
	}
	
	public function getContent() {
		$pages = $this->questionnaire->pages;
		
		//used only in dynamicInput:
		$last_completed_cookie_name = sprintf(self::COOKIE_LAST_COMPLETED, $this->study->id, $this->questionnaire->internalId);
		$last_completed = (isset($_COOKIE[$last_completed_cookie_name])) ? $_COOKIE[$last_completed_cookie_name] : 0;
		
		$is_last_page = $this->pageInt + 1 == sizeof($pages);
		
		$page = $pages[$this->pageInt];
		$inputs = $page->inputs;
		
		
		
		$output = '';
	
		if(!Extra::questionnaire_isActive($this->questionnaire))
			return '<p class="highlight center">' .Lang::get('error_questionnaire_not_active') .'</p>';
		
		
		if($this->success_saving) {
			return '<p class="center">'
				.((isset($this->study->webQuestionnaireCompletedInstructions) && strlen($this->study->webQuestionnaireCompletedInstructions))
					? $this->study->webQuestionnaireCompletedInstructions
					: Lang::get('default_webQuestionnaireCompletedInstructions'))
				.'</p></div>';
		}
		else if($this->data_missing) {
			$output .= '<p class="highlight center">' .Lang::get('error_missing_requiredField') .'</p>';
		}
		
		$output .= '<form id="participant_box" class="small_text" action="" method="post">
			<span class="highlight" data-bind="text: Lang.colon_user_id">' .Lang::get('user_id') .':</span>
			<span>' .$this->participant .'</span>
			<input type="hidden" name="participant" value="' .$this->participant .'"/>
			<input type="submit" name="delete_participant" value="' .Lang::get('change') .'"/>
		</form>
		<hr/>
		
		<form class="colored_lines" id="questionnaire_box" method="post">';
		
		$inputObj = new Inputs($this->study->id, $last_completed, $this->pageInt, $this->dataCache);
		
		if(isset($page->randomized) && $page->randomized) {
			foreach($inputs as $i => $input) {
				$input->phpIndex = $i;
			}
			shuffle($inputs);
			
			foreach($inputs as $input) {
				$output .= $inputObj->draw_input($input, $input->phpIndex);
			}
		}
		else {
			foreach($inputs as $i => $input) {
				$output .= $inputObj->draw_input($input, $i);
			}
		}
		
		
		$output .= "<input type=\"hidden\" name=\"participant\" value=\"$this->participant\"/>
			<input type=\"hidden\" name=\"informed_consent\" value=\"1\"/>
			<input type=\"hidden\" name=\"form_started\" value=\"$this->form_started\"/>";
		
		
		foreach($this->dataCache as $key => $value) {
			$output .= "<input type=\"hidden\" name=\"cached_values[$key]\" value=\"$value\"/>";
		}
		
		$output .= '<p class="small_text spacing_top">* input required</p>'
			.($is_last_page
				? '<input type="submit" name="save" class="right" value="' .Lang::get('save').'"/>'
				: '<input type="submit" name="continue" class="right" value="'.Lang::get('continue').'"/>')
			.'
			<input type="hidden" name="page" value="' .$this->pageInt .'"/>
		</form>';
		
		return $output;
	}
}