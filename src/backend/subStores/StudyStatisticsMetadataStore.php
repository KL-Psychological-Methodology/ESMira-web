<?php

namespace backend\subStores;

use backend\CriticalError;
use backend\dataClasses\StudyStatisticsEntry;
use stdClass;

interface StudyStatisticsMetadataStore {
	public function loadMetadataCollection(): array;
	
	public function addMetadataEntry(string $key, StudyStatisticsEntry $entry);
	
	/**
	 * @throws CriticalError
	 */
	public function saveChanges();
}