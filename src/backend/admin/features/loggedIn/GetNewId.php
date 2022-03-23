<?php

namespace backend\admin\features\loggedIn;


use backend\admin\IsLoggedIn;
use backend\Files;
use backend\Output;

class GetNewId extends IsLoggedIn {
	
	function exec() {
		$forQuestionnaire = $_GET['for'] === 'questionnaire';
		$filtered = $forQuestionnaire ? json_decode(file_get_contents('php://input')) : [];
		
		$study_index = file_exists(Files::get_file_studyIndex()) ? unserialize(file_get_contents(Files::get_file_studyIndex())) : [];
		
		$i = 0;
		do {
			$id = $forQuestionnaire ? $this->getQuestionnaireId() : $this->getStudyId();
			
			if(++$i > 1000)
				Output::error('Could not find an unused id...');
		} while(file_exists(Files::get_folder_study($id)) || isset($study_index["~$id"]) || isset($filtered[$id]));
		Output::successObj($id);
	}
}