<?php

namespace test\backend;

use backend\ResponsesIndex;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class ResponsesIndexTest extends BaseTestSetup {
	function test() {
		$index = new ResponsesIndex(
			[
				'key1',
				'key2',
				'key3'
			],
			['key1' => 'type']
		);
		$this->assertEquals([
			'key1',
			'key2',
			'key3'
		], $index->keys);
		$this->assertEquals(['key1' => 'type'], $index->types);
		
		$index->addInput((object) ['name' => 'textName', 'responseType' => 'text']);
		$index->addInput((object) ['name' => 'dynamicName', 'responseType' => 'dynamic_input']);
		$index->addInput((object) ['name' => 'appName', 'responseType' => 'app_usage']);
		$index->addInput((object) ['name' => 'photoName', 'responseType' => 'photo']);
		$index->addInput((object) ['name' => 'elseName', 'responseType' => 'something else']);
		
		$this->assertEquals([
			'key1',
			'key2',
			'key3',
			'dynamicName',
			'dynamicName~index',
			'appName',
			'appName~usageCount',
			'photoName',
			'elseName'
		], $index->keys);
		
		$index->addName('added');
		
		$this->assertEquals([
			'key1',
			'key2',
			'key3',
			'dynamicName',
			'dynamicName~index',
			'appName',
			'appName~usageCount',
			'photoName',
			'elseName',
			'added'
		], $index->keys);
		
		$this->assertEquals(['key1' => 'type', 'photoName' => 'image'], $index->types);
	}
}