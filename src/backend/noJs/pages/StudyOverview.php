<?php

namespace backend\noJs\pages;

use backend\CreateDataSet;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use Exception;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\NoJsMain;
use backend\noJs\Page;
use stdClass;

class StudyOverview implements Page {
	/**
	 * @var stdClass
	 */
	private $study;
	/**
	 * @var string
	 */
	private $accessKey;
	
	/**
	 * @throws PageFlowException
	 * @throws ForwardingException
	 * @throws CriticalException
	 */
	public function __construct() {
		$studyData = NoJsMain::getStudyData();
		$this->study = $studyData->study;
		$this->accessKey = $studyData->accessKey;
		
		if(!isset($_GET['ref']))
			CreateDataSet::saveWebAccess($this->study->id, 'navigatedFromHome_noJs');
	}
	
	public function getTitle(): string {
		return $this->study->title;
	}
	
	public function getContent(): string {
		$studyId = $this->study->id;
		$output = '';
		if(isset($this->study->studyDescription) && strlen($this->study->studyDescription))
			$output .= '<div class="scrollBox">' .$this->study->studyDescription .'</div>';
		
		$output .= '<br/><div class="titleRow">' .Lang::get('questionnaires') .'</div>';
		foreach($this->study->questionnaires as $questionnaire) {
			if(!NoJsMain::questionnaireIsActive($questionnaire))
				continue;
			
			$name = $questionnaire->title ?? Lang::get('questionnaire');
			$qId = $questionnaire->internalId;
			if($this->accessKey)
				$output .= "<a class=\"vertical verticalPadding\" href=\"?key=$this->accessKey&id=$studyId&qid=$qId\">$name</a>";
			else
				$output .= "<a class=\"vertical verticalPadding\" href=\"?id=$studyId&qid=$qId\">$name</a>";
		}
		
		return $output;
	}
}