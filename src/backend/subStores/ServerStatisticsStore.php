<?php

namespace backend\subStores;

use backend\Configs;
use backend\Main;
use stdClass;
use Throwable;

abstract class ServerStatisticsStore {
	protected function createNewStatisticsDataObj(): stdClass {
		
		$totalStudies = 0;
		
		try {
			$studyStore = Configs::getDataStore()->getStudyStore();
			foreach($studyStore->getStudyIdList() as $studyId) {
				$study = $studyStore->getStudyConfig($studyId);
				if($study->published ?? false)
					$totalStudies += 1;
			}
		}
		catch(Throwable $e) {
			Main::report("Something went wrong when counting active studies\n\n" .$e->getMessage());
		}
		
		return (object)[
			'days' => new stdClass(),
			'week' => (object)[
				'questionnaire' => [0,0,0,0,0,0,0],
				'joined' => [0,0,0,0,0,0,0]
			],
			'total' => (object)[
				'studies' => $totalStudies,
				'users' => 0,
				'android' => 0,
				'ios' => 0,
				'web' => 0,
				'questionnaire' => 0,
				'joined' => 0,
				'quit' => 0
			],
			'created' => time()
		];
	}
	public abstract function update(callable $callback);
	public abstract function getStatisticsAsJsonString(): string;
}