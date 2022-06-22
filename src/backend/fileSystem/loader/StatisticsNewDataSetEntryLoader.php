<?php

namespace backend\fileSystem\loader;

use backend\DataSetCacheStatisticsEntry;

class StatisticsNewDataSetEntryLoader {
	public static function import(string $data): DataSetCacheStatisticsEntry {
		return unserialize($data);
	}
	
	public static function export(DataSetCacheStatisticsEntry $entry): string {
		return serialize($entry);
	}
}