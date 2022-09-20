<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use stdClass;

interface StatisticsStoreWriter {
	public function incrementStudies();
	public function decrementStudies();
	public function incrementUser(int $num = 1);
	public function incrementAndroid(int $num = 1);
	public function incrementIos(int $num = 1);
	public function incrementWeb(int $num = 1);
	public function addDataToDay(int $oldestAllowedEntryTime, int $startOfDay, string $appType, string $appVersion, int $questionnaireEvents, int $joinEvents);
}