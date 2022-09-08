<?php

namespace backend\noJs\pages;

use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseNoJsTestSetup;

require_once __DIR__ .'/../../../../backend/autoload.php';

class StudyOverviewTest extends BaseNoJsTestSetup {
	
	protected $configs = [
		123 => [
			'id' => 123,
			'title' => 'study1',
			'studyDescription' => 'studyDescription',
			'questionnaires' => [
				['internalId' => 1111, 'title' => 'questionnaire1', 'pages' => [[]]],
				['internalId' => 2222, 'title' => 'questionnaire2', 'pages' => []]
			]
		],
	];
	
	function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$this->setUpForStudyData($observer);
		return $observer;
	}
	
	function test() {
		$obj = new StudyOverview();
		$study = $this->configs[123];
		$questionnaires = $study['questionnaires'];
		$content = $obj->getContent();
		$this->assertStringContainsString($study['title'], $obj->getTitle());
		$this->assertStringContainsString('id=123&qid=1111', $content);
		$this->assertStringContainsString($study['studyDescription'], $content);
		$this->assertStringContainsString($questionnaires[0]['title'], $content);
		$this->assertStringNotContainsString($questionnaires[1]['title'], $content);
	}
	function test_with_accessKey() {
		$this->setGet(['id' => 123, 'key' => 'key1']);
		$obj = new StudyOverview();
		$study = $this->configs[123];
		$questionnaires = $study['questionnaires'];
		$content = $obj->getContent();
		$this->assertStringContainsString($study['title'], $obj->getTitle());
		$this->assertStringContainsString('key=key1&id=123&qid=1111', $content);
		$this->assertStringContainsString($study['studyDescription'], $content);
		$this->assertStringContainsString($questionnaires[0]['title'], $content);
		$this->assertStringNotContainsString($questionnaires[1]['title'], $content);
	}
}