<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Base;
use backend\Configs;
use backend\CreateDataSet;
use backend\Files;
use backend\Output;
use backend\Permission;
use stdClass;


const ONE_DAY = 86400; //in seconds: 60*60*24

class SaveStudy extends HasWritePermission {
	private function check_axis(&$axisData, &$index, &$observed_variables, $storageType, $timeInterval) {
		if(!isset($axisData))
			return;
		
		$key = isset($axisData->variableName) ? $axisData->variableName : '';
		if(!strlen($key))
			return;
		
		$conditionString = $this->get_conditionString($key, $storageType, $timeInterval, $axisData->conditions);
		if(isset($index[$conditionString])) {
			$axisData->observedVariableIndex = $index[$conditionString];
		}
		else {
			if(!isset($observed_variables->{$key})) {
				$observed_variables->{$key} = [];
			}
			$keyBox = &$observed_variables->{$key};
			$conditionType = isset($axisData->conditionType) ? $axisData->conditionType : CreateDataSet::CONDITION_TYPE_ALL;
			$obj = (object)['conditions' => $axisData->conditions, 'conditionType' => $conditionType, 'storageType' => $storageType, 'timeInterval' => $timeInterval];
			
			array_push($keyBox, $obj);
			$index[$conditionString] = $axisData->observedVariableIndex = sizeof($keyBox)-1;
		}
	}
	private function set_observedVariables_from_axis(&$studyCollection, $configName, &$public_observed_variables=null, &$public_index=null) {
		$langConfigs = [];
		$defaultConfig = $studyCollection->_->{$configName};
		foreach($studyCollection as $code => &$study) {
			if($code !== '_')
				$langConfigs[$code] = &$study->{$configName};
		}
		
		$observedVariables = new stdClass; //new stdClass translates into an empty object (instead of array) in JSON
		$index = [];
		
		
		foreach($defaultConfig->charts as $chart_i => &$defaultChart) {
			$dataType = isset($defaultChart->dataType) ? number_format($defaultChart->dataType) : CreateDataSet::STATISTICS_DATATYPES_DAILY;
			switch($dataType) {
				case CreateDataSet::STATISTICS_DATATYPES_SUM:
				case CreateDataSet::STATISTICS_DATATYPES_DAILY:
					$timeInterval = ONE_DAY;
					$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
					break;
				case CreateDataSet::STATISTICS_DATATYPES_FREQ_DISTR:
					$timeInterval = 0;
					$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_FREQ_DISTR;
					break;
				case CreateDataSet::STATISTICS_DATATYPES_XY:
				default:
					$timeInterval = Configs::get('smallest_timed_distance');
					$storageType = CreateDataSet::STATISTICS_STORAGE_TYPE_TIMED;
					break;
			}
			
			
			foreach($defaultChart->axisContainer as $axis_i => &$defaultAxisContainer) {
				$this->check_axis($defaultAxisContainer->yAxis, $index, $observedVariables, $storageType, $timeInterval);
				
				if($dataType == CreateDataSet::STATISTICS_DATATYPES_XY)
					$this->check_axis($defaultAxisContainer->xAxis, $index, $observedVariables, $storageType, $timeInterval);
				else
					$defaultAxisContainer->xAxis->observedVariableIndex = -1;
				
				foreach($langConfigs as &$config) {
					$langAxis = &$config->charts[$chart_i]->axisContainer[$axis_i];
					$langAxis->yAxis->observedVariableIndex = $defaultAxisContainer->yAxis->observedVariableIndex;
					$langAxis->xAxis->observedVariableIndex = $defaultAxisContainer->xAxis->observedVariableIndex;
				}
				unset($config);
			}
			
			if(isset($defaultChart->displayPublicVariable) && $defaultChart->displayPublicVariable && $public_observed_variables) {
				foreach($defaultChart->publicVariables as $axis) {
					$this->check_axis($axis->yAxis, $public_index, $public_observed_variables, $storageType, $timeInterval);
					if($dataType == CreateDataSet::STATISTICS_DATATYPES_XY)
						$this->check_axis($axis->xAxis, $public_index, $public_observed_variables, $storageType, $timeInterval);
				}
			}
			
			$defaultChart->storageType = $storageType;
		}
		
		$defaultConfig->observedVariables = $observedVariables;
		foreach($langConfigs as &$config) {
			$config->observedVariables = $observedVariables;
		}
		
		return $index;
	}
	
	
	function exec() {
		$studyCollection_json = file_get_contents('php://input');
		
		if(!($studyCollection = json_decode($studyCollection_json)))
			Output::error('Unexpected data');
		
		if(!isset($studyCollection->_))
			Output::error('No default study');
		
		$study = $studyCollection->_;
		
		if(!isset($study->id) || $study->id != $this->study_id)
			Output::error("Problem with study id! $this->study_id !=" .$study->id);
		
		$file_config = Files::get_file_studyConfig($this->study_id);
		
		if(isset($_GET['lastChanged']) && file_exists($file_config) && filemtime($file_config) > $_GET['lastChanged'])
			Output::error('The study configuration was changed (by another user?) since you last loaded it. You can not save your changes. Please reload the page.');
		
		
		$study_index = file_exists(Files::get_file_studyIndex()) ? unserialize(file_get_contents(Files::get_file_studyIndex())) : [];
		
		//*****
		//check and prepare questionnaires:
		//*****
		
		$keys_questionnaire_array = $this->checkUnique_and_collectKeys($study, $study_index);
		
		
		//*****
		//create folders
		//*****
		
		$folder_study = Files::get_folder_study($this->study_id);
		$folder_langConfigs = Files::get_folder_langs($this->study_id);
		$folder_study_responses = Files::get_folder_responses($this->study_id);
		$folder_study_messages = Files::get_folder_messages($this->study_id);
		$folder_study_messages_user = Files::get_folder_messages_archive($this->study_id);
		$folder_study_messages_outgoing = Files::get_folder_messages_pending($this->study_id);
		$folder_study_messages_unread = Files::get_folder_messages_unread($this->study_id);
		$folder_study_responsesIndex = Files::get_folder_responsesIndex($this->study_id);
		$folder_study_statistics = Files::get_folder_statistics($this->study_id);
		$folder_study_token = Files::get_folder_userData($this->study_id);
		
		$this->create_folder($folder_study);
		$this->create_folder($folder_langConfigs);
		$this->create_folder($folder_study_token);
		$this->create_folder($folder_study_messages);
		$this->create_folder($folder_study_messages_user);
		$this->create_folder($folder_study_messages_outgoing);
		$this->create_folder($folder_study_messages_unread);
		$this->create_folder($folder_study_responses);
		$this->create_folder($folder_study_responsesIndex);
		$this->create_folder($folder_study_statistics);
		
		
		//*****
		//save questionnaire index (has to happen after folders are created)
		//*****
		
		foreach($study->questionnaires as $i => $q) {
			$this->write_indexAndResponses_files($study, $q->internalId, $keys_questionnaire_array[$i]);
		}
		
		
		//*****
		//Creating observable variables and statistics
		//*****
		
		
		
		
		//publicStatistics:
		$public_index = $this->set_observedVariables_from_axis($studyCollection, 'publicStatistics');
		
		//personalStatistics:
		//Note: $public_index can still change when global variables are used in personal charts
		// so we need this before we save the public statistics file
		$this->set_observedVariables_from_axis($studyCollection, 'personalStatistics', $study->publicStatistics->observedVariables, $public_index);
		
		
		//statistics files:
		$this->write_statistics($study);
		
		
		//*****
		//saving files
		//*****
		
		//
		//publish / unpublish study
		//
		if($this->is_admin || Permission::has_permission($this->study_id, 'publish')) {
			$removeCount = $this->remove_study_from_index($study_index, $this->study_id);
			
			if(isset($study->published) && $study->published) {
				//entries for accessKeys:
				if(isset($study->accessKeys) && count($study->accessKeys)) {
					foreach($study->accessKeys as $key => $value) {
						$value = strtolower($value);
						foreach($studyCollection as &$langStudy) {
							$langStudy->accessKeys[$key] = $value;
						}
						unset($langStudy);
						
						if(!Base::check_input($value))
							Output::error("No special characters are allowed in access keys.\n'$value'");
						else if(!preg_match("/^([a-zA-Z][a-zA-Z0-9]*)$/", $value))
							Output::error("Access keys need to start with a character.");
						else if(!isset($study_index[$value]))
							$study_index[$value] = [$this->study_id];
						else if(!in_array($this->study_id, $study_index[$value]))
							array_push($study_index[$value], $this->study_id);
					}
				}
				else {
					if(!isset($study_index['~open']))
						$study_index['~open'] = [$this->study_id];
					else
						array_push($study_index['~open'], $this->study_id);
				}
				
				//entries for questionnaire internalIds
				foreach($study->questionnaires as $q) {
					$key = '~'.$q->internalId;
					
					$study_index[$key] = [$this->study_id]; //this doesnt have to be an array. But we try to stay consistent with access key entries
				}
				
				//update server statistics:
				if(!$removeCount) {
					Base::update_serverStatistics(function(&$statistics) {
						$statistics->total->studies += 1;
					});
				}
			}
			else if($removeCount) {
				Base::update_serverStatistics(function(&$statistics) {
					$statistics->total->studies -= 1;
				});
			}
			$this->write_file(Files::get_file_studyIndex(), serialize($study_index));
		}
		else {
			$old_study = file_exists($file_config) ? json_decode(file_get_contents($file_config)) : [];
			
			foreach($studyCollection as &$langStudy) {
				$langStudy->accessKeys = isset($old_study->accessKeys) ? $old_study->accessKeys : [];
				$langStudy->published = isset($old_study->published) ? $old_study->published : false;
			}
			unset($langStudy);
		}
		
		//
		//save study config
		//
		if(!isset($study->version) || $study->version === 0) {
			foreach($studyCollection as &$langStudy) {
				$langStudy->version = 1;
				$langStudy->subVersion = 0;
			}
			unset($langStudy);
		}
		else {
			foreach($studyCollection as &$langStudy) {
				$langStudy->new_changes = true;
				$langStudy->subVersion += 1;
			}
		}
		
		//delete old language files
		$this->empty_folder(Files::get_folder_langs($this->study_id));
		
		$studies_json = [];
		foreach($studyCollection as $code => $s) {
			$study_json = json_encode($s);
			$this->write_file($code === '_' ? $file_config : Files::get_file_langConfig($this->study_id, $code), $study_json);
			$studies_json[] = "\"$code\":$study_json";
		}
		
		
		//
		//create web_access and events file
		//
		$this->write_indexAndResponses_files($study, Files::FILENAME_EVENTS, self::KEYS_EVENT_RESPONSES);
		$this->write_indexAndResponses_files($study, Files::FILENAME_WEB_ACCESS, self::KEYS_WEB_ACCESS);
		
		
		//
		//save index-files
		//
		$metadata = Base::get_newMetadata($study);
		$this->write_file(Files::get_file_studyMetadata($this->study_id), serialize($metadata));
		$sentChanged = time();
		Output::successString("{\"lastChanged\":$sentChanged,\"json\":{" .implode(',', $studies_json) ."}}");
	}
}