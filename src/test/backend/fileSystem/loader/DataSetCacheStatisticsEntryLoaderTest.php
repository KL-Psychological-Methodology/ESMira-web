<?php

namespace test\backend\fileSystem\loader;

require_once __DIR__ .'/../../../../backend/autoload.php';

use backend\DataSetCacheStatisticsEntry;
use backend\fileSystem\loader\StatisticsNewDataSetEntryLoader;
use test\testConfigs\BaseTestSetup;

class DataSetCacheStatisticsEntryLoaderTest extends BaseTestSetup {
	function test_import_and_export() {
		$source = new DataSetCacheStatisticsEntry('key', 5, 123, 'answer');
		$exported = StatisticsNewDataSetEntryLoader::import(StatisticsNewDataSetEntryLoader::export($source));
		
		$this->assertEquals($source->key, $exported->key);
		$this->assertEquals($source->index, $exported->index);
		$this->assertEquals($source->timestamp, $exported->timestamp);
		$this->assertEquals($source->answer, $exported->answer);
	}
}