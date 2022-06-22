<?php

namespace backend\noJs\pages;

use backend\CreateDataSet;
use backend\CriticalError;
use backend\PageFlowException;
use Exception;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\NoJsMain;
use backend\noJs\InputToString;
use backend\noJs\Page;
use stdClass;

class QuestionnaireAttend implements Page {
	const COOKIE_LAST_COMPLETED = 'last_completed%1$d_%2$d';
	
	/**
	 * @var stdClass
	 */
	private $study;
	/**
	 * @var int
	 */
	private $pageInt;
	/**
	 * @var int
	 */
	private $formStarted;
	/**
	 * @var stdClass|null
	 */
	private $questionnaire;
	/**
	 * @var bool
	 */
	private $successSaving = false;
	/**
	 * @var string|null
	 */
	private $errorMsg = null;
	/**
	 * @var bool
	 */
	private $dataMissing = false;
	/**
	 * @var string
	 */
	private $participant;
	/**
	 * @var array
	 */
	private $dataCache;
	
	/**
	 * @throws ForwardingException
	 * @throws CriticalError
	 * @throws PageFlowException
	 */
	public function __construct() {
		$studyData = NoJsMain::getStudyData();
		$this->study = $studyData->study;
		
		if(isset($this->study->publishedWeb) && !$this->study->publishedWeb)
			throw new ForwardingException(new AppInstall());
		
		if(!$studyData->questionnaire)
			throw new ForwardingException(new StudyOverview());
		
		$this->questionnaire = $studyData->questionnaire;
		$this->formStarted = (int) ($_POST['form_started'] ?? 0 ?: Main::getMilliseconds());
		$this->dataCache = $_POST['responses'] ?? []; //TODO: save and load data from response_cache cookie
		
		if(isset($_POST['delete_participant'])) {
			try {
				NoJsMain::saveDataset(CreateDataSet::DATASET_TYPE_QUIT, $_POST['participant'], $this->study);
			}
			catch(CriticalError $e) {
				$this->errorMsg = $e->getMessage();
			}
			Main::deleteCookie('participant'.$this->study->id);
			unset($_POST['participant']);
			throw new ForwardingException(new GetParticipant());
		}
		
		$this->DoForwarding();
		$this->doPageFinishActions(); //if data was sent, we check it now so we can show the correct page number in title
	}
	
	/**
	 * @throws ForwardingException
	 */
	private function setParticipant() {
		if(!isset($_POST['participant']) || !self::participantIsValid($_POST['participant']))
			throw new ForwardingException(new GetParticipant());
		
		$this->participant = $_POST['participant'];
		if(isset($_POST['new_participant'])) {
			try {
				NoJsMain::saveDataset(CreateDataSet::DATASET_TYPE_JOINED, $this->participant, $this->study);
			}
			catch(CriticalError $e) {
				$this->errorMsg = $e->getMessage();
			}
		}
		
		$studyId = $this->study->id;
		Main::setCookie("participant$studyId", $this->participant);
	}
	
	/**
	 * @throws ForwardingException
	 */
	private function DoForwarding() {
		$studyId = $this->study->id;
		
		if(isset($this->study->informedConsentForm) && strlen($this->study->informedConsentForm) && !isset($_COOKIE["informed_consent$studyId"])) {
			if(!isset($_POST['informed_consent']))
				throw new ForwardingException(new InformedConsent());
			else
				Main::setCookie("informed_consent$studyId", '1');
		}
		
		if(!isset($_COOKIE["participant$studyId"]) || !self::participantIsValid($_COOKIE["participant$studyId"]))
			$this->setParticipant();
		else
			$this->participant = $_COOKIE["participant$studyId"];
	}
	
	private function considerResponseType(stdClass $input, string $value): string {
		switch($input->responseType ?? '') {
			case 'time':
				if(isset($input->forceInt) && $input->forceInt) {
					$split = explode(':', $value);
					if(sizeof($split) == 2)
						$value = $split[0] * 60 + $split[1];
				}
				break;
			default:
				break;
		}
		return $value;
	}
	
	/**
	 * @throws PageFlowException
	 */
	private function extractInputs() {
		$pages = $this->questionnaire->pages;
		$pageBefore = (int) ($_POST['page'] ?? 0);
		
		if(!isset($pages[$pageBefore]))
			throw new PageFlowException(Lang::get('error_unknown_data'));
		
		$inputsBefore = $pages[$pageBefore]->inputs;
		
		$responses = $_POST['responses'] ?? [];
		
		
		foreach($inputsBefore as $input) {
			if(!isset($input->name))
				continue;
			$value = $responses[$input->name] ?? '';
			
			if(isset($input->required) && $input->required && empty($value)) {
				$this->dataMissing = true;
				break;
			}
			
			$this->dataCache[$input->name] = $this->considerResponseType($input, $value);
		}
	}
	
	/**
	 * @throws PageFlowException
	 */
	private function doPageFinishActions() {
		$pageBefore = (int) ($_POST['page'] ?? 0);
		
		$this->extractInputs();
		
		if(!isset($_POST) || (!isset($_POST['save']) && !isset($_POST['continue']))) {
			$this->pageInt = 0;
			CreateDataSet::saveWebAccess($this->study->id, 'questionnaire ' .$this->questionnaire->internalId);
			return;
		}
		
		if($this->dataMissing) {
			$this->pageInt = $pageBefore;
			return;
		}
		
		if(isset($_POST['save'])) {
			$this->dataCache['formDuration'] = Main::getMilliseconds() - $this->formStarted; //used in CreateDataSet
			
			try {
				NoJsMain::saveDataset(CreateDataSet::DATASET_TYPE_QUESTIONNAIRE, $this->participant, $this->study, $this->questionnaire, $this->dataCache);
				
				$this->successSaving = true;
			}
			catch(CriticalError $e) {
				$this->successSaving = false;
				$this->errorMsg = $e->getMessage();
			}
			
			if($this->successSaving) {
				$lastCompletedCookieName = sprintf(self::COOKIE_LAST_COMPLETED, $this->study->id, $this->questionnaire->internalId);
				Main::setCookie($lastCompletedCookieName, time());
				$this->pageInt = $pageBefore;
			}
			else
				$this->pageInt = $pageBefore;
		}
		else
			$this->pageInt = $pageBefore + 1;
	}
	
	
	static function participantIsValid($participant): bool {
		return strlen($participant) >= 1 && preg_match('/^[a-zA-Z0-9À-ž_\s\-()]+$/', $participant);
	}
	
	public function getTitle(): string {
		$pagesSize = sizeof($this->questionnaire->pages);
		$output = $this->questionnaire->title ?? Lang::get('questionnaire');
		if($pagesSize > 1)
			$output .= ' (' .($this->pageInt+1).'/'.$pagesSize.')';
		return $output;
	}
	
	public function getContent(): string {
		$pages = $this->questionnaire->pages;
		
		//used only in dynamicInput:
		$lastCompletedCookieName = sprintf(self::COOKIE_LAST_COMPLETED, $this->study->id, $this->questionnaire->internalId);
		$lastCompleted = $_COOKIE[$lastCompletedCookieName] ?? 0;
		
		$isLastPage = $this->pageInt + 1 == sizeof($pages);
		
		$page = $pages[$this->pageInt];
		$inputs = $page->inputs;
		
		
		$output = '';
	
		if(!NoJsMain::questionnaireIsActive($this->questionnaire))
			return '<p class="highlight center">' .Lang::get('error_questionnaire_not_active') .'</p>';
		
		
		if($this->errorMsg) {
			$output .= '<p class="highlight center">' .$this->errorMsg .'</p>';
		}
		else if($this->successSaving) {
			return '<p class="center">'
				.((isset($this->study->webQuestionnaireCompletedInstructions) && strlen($this->study->webQuestionnaireCompletedInstructions))
					? $this->study->webQuestionnaireCompletedInstructions
					: Lang::get('default_webQuestionnaireCompletedInstructions'))
				.'</p></div>';
		}
		else if($this->dataMissing) {
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
		
		$inputObj = new InputToString($this->study->id, $lastCompleted, $this->pageInt, $this->dataCache);
		
		if(isset($page->randomized) && $page->randomized)
			shuffle($inputs);
		
		$visibleIndex = [];
		foreach($inputs as $input) {
			if(!isset($input->name))
				continue;
			$visibleIndex[$input->name] = true;
			$output .= $inputObj->drawInput($input);
		}
		
		
		$output .= "<input type=\"hidden\" name=\"participant\" value=\"$this->participant\"/>
			<input type=\"hidden\" name=\"informed_consent\" value=\"1\"/>
			<input type=\"hidden\" name=\"form_started\" value=\"$this->formStarted\"/>";
		
		
		foreach($this->dataCache as $key => $value) {
			if(isset($visibleIndex[$key]))
				continue;
			$output .= "<input type=\"hidden\" name=\"responses[$key]\" value=\"$value\"/>";
		}
		
		$output .= '<p class="small_text spacing_top">* input required</p>'
			.($isLastPage
				? '<input type="submit" name="save" class="right" value="' .Lang::get('save').'"/>'
				: '<input type="submit" name="continue" class="right" value="'.Lang::get('continue').'"/>')
			.'
			<input type="hidden" name="page" value="' .$this->pageInt .'"/>
		</form>';
		
		return $output;
	}
}