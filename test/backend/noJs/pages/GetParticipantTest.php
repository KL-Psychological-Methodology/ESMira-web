<?php

namespace backend\noJs\pages;

use backend\noJs\pages\GetParticipant;
use PHPUnit\Framework\MockObject\Stub;
use testConfigs\BaseNoJsTestSetup;

require_once __DIR__ . '/../../../autoload.php';

class GetParticipantTest extends BaseNoJsTestSetup {
	protected $configs = [
		123 => ['id' => 123, 'chooseUsernameInstructions' => 'chooseUsernameInstructions1'],
	];
	
	function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		$this->setUpForStudyData($observer);
		return $observer;
	}
	
	function test() {
		$obj = new GetParticipant();
		
		$content = $obj->getContent();
		$this->assertStringContainsString('chooseUsernameInstructions', $content);
		$this->assertNotEmpty($obj->getTitle());
	}
}