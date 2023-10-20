<?php

namespace backend\noJs\pages;

use backend\CreateDataSet;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\NoJsMain;
use backend\noJs\Page;
use backend\QuestionnaireSaver;
use stdClass;

class QuestionnaireAttend implements Page {
	/**
	 * @var stdClass
	 */
	private $study;
	/**
	 * @var stdClass|null
	 */
	private $questionnaire;
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
	 * @var QuestionnaireSaver
	 */
	private $inputObj;
	
	/**
	 * @throws ForwardingException
	 * @throws CriticalException
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
		$this->inputObj = new QuestionnaireSaver($studyData->study, $studyData->questionnaire);
		
		
		if($this->inputObj->finishActionNeeded()) {
			try {
				$this->dataMissing = $this->inputObj->doPageFinishActions('Web-NoJs') != null;
			}
			catch(CriticalException $e) {
				$this->errorMsg = $e->getMessage();
			}
		}
	}
	
	public function getTitle(): string {
		return $this->inputObj->getTitle();
	}
	
	public function getContent(): string {
		$output = '';
		
		if(!NoJsMain::questionnaireIsActive($this->questionnaire))
			return '<p class="highlight center">' .Lang::get('error_questionnaire_not_active') .'</p>';
		
		
		if($this->errorMsg) {
			$output .= '<p class="highlight center">' .$this->errorMsg .'</p>';
		}
		else if($this->inputObj->isCompleted) {
			return '<p class="center">'
				.($this->study->webQuestionnaireCompletedInstructions
					?? Lang::get('default_webQuestionnaireCompletedInstructions')
					?: Lang::get('default_webQuestionnaireCompletedInstructions')
				)
				.'</p></div>';
		}
		else if($this->dataMissing) {
			$output .= '<p class="highlight center">' .Lang::get('error_missing_requiredField') .'</p>';
		}
		
		
		$output .= $this->inputObj->drawPage();
		
		return $output;
	}
}