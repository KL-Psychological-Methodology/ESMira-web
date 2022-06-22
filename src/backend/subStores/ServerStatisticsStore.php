<?php

namespace backend\subStores;

use stdClass;

abstract class ServerStatisticsStore {
	protected function createNewStatisticsDataObj(): stdClass {
		return (object)[
			'days' => new stdClass(),
			'week' => (object)[
				'questionnaire' => [0,0,0,0,0,0,0],
				'joined' => [0,0,0,0,0,0,0]
			],
			'total' => (object)[
				'studies' => 0,
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