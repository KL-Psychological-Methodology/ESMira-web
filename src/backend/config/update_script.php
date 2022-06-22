<?php
declare(strict_types=1);

use backend\Files;

function run_updateScript(string $fromVersion) {
	switch($fromVersion) {
		case '1.5.0':
			$handle = opendir(Files::get_folder_studies());
			while($studyId = readdir($handle)) {
				if($studyId[0] == '.' || $studyId == Files::FILENAME_STUDY_INDEX)
					continue;
				
				$pathStatisticsNewData = Files::get_file_statisticsNewData($studyId);
				if(!file_exists($pathStatisticsNewData))
					continue;
				
				$newDataCollection = file($pathStatisticsNewData);
				
				$newContent = '';
				foreach($newDataCollection as $line) {
					$newData = explode('|', trim($line));
					if(count($newData) != 4)
						continue; //happens when the line is empty
					list($key, $index, $timestamp, $answer) = $newData;
					$newContent .= serialize([$key, $index, $timestamp, $answer]);
				}
				file_put_contents($pathStatisticsNewData, $newContent, LOCK_EX);
			}
		default:
	}
}