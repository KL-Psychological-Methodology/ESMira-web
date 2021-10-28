<?php

namespace phpClasses\noJs\pages;

use Exception;
use phpClasses\Base;
use phpClasses\noJs\ForwardingException;
use phpClasses\noJs\Lang;
use phpClasses\noJs\Extra;
use phpClasses\noJs\Page;

class StudyOverview implements Page {
	private $study;
	private $access_key;
	
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
		
		$this->access_key = $studyData['accessKey'];
		
		if(!isset($_GET['ref']))
			Base::save_webAccess($this->study->id, 'navigatedFromHome_noJs');
	}
	
	public function getTitle() {
		return $this->study->title;
	}
	
	public function getContent() {
		$study_id = $this->study->id;
		$output = '';
		if(isset($this->study->studyDescription) && strlen($this->study->studyDescription))
			$output .= '<div class="scrollBox">' .$this->study->studyDescription .'</div>';
		
		$output .= '<br/><div class="title-row">' .Lang::get('colon_questionnaires') .'</div>';
		foreach($this->study->questionnaires as $questionnaire) {
			if(!Extra::questionnaire_isActive($questionnaire))
				continue;
			
			$name = isset($questionnaire->title) ? $questionnaire->title : Lang::get('questionnaire');
			$qId = $questionnaire->internalId;
			if($this->access_key)
				$output .= "<a class=\"vertical verticalPadding\" href=\"?key=$this->access_key&id=$study_id&qid=$qId\">$name</a>";
			else
				$output .= "<a class=\"vertical verticalPadding\" href=\"?id=$study_id&qid=$qId\">$name</a>";
		}
		
		return $output;
	}
}