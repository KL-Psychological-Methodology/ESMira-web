<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use backend\dataClasses\StudyStatisticsEntry;
use stdClass;

interface StudyStatisticsMetadataStore {
	public function loadMetadataCollection(): array;
	
	public function addMetadataEntry(string $key, StudyStatisticsEntry $entry);
	
	/**
	 * @throws CriticalException
	 */
	public function saveChanges();
}