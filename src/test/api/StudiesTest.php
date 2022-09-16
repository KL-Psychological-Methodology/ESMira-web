<?php

namespace test\api;

use backend\CriticalError;
use backend\JsonOutput;
use backend\Permission;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyMetadataStore;
use backend\subStores\StudyStore;
use backend\subStores\AccountStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';

class StudiesTest extends BaseApiTestSetup {
	private $isAdmin = false;
	private $hasException = false;
	private $allJsons = [
		123 => '{123}',
		234 => '{234}',
		345 => '{345}',
		456 => '{456}',
		567 => '{567}'
	];
	private $allLangJsons = [
		123 => '{123,"lang"}',
		234 => '{234,"lang"}',
		345 => '{345,"lang"}',
		456 => '{456,"lang"}',
		567 => '{567,"lang"}'
	];
	private $accessKeyIndex = [
		'key1' => [123, 234],
		'key2' => [345, 456],
		'' => [234, 567]
	];
	private $accessKeys = ['key1', 'key2'];
	private $allStudies = [123, 234, 345, 456, 567];
	private $readPermissionStudies = [234, 567];
	private $msgPermissionStudies = [456, 234];
	private $writePermissionStudies = [123, 234];
	
	public function setUp(): void {
		parent::setUp();
		$this->isAdmin = false;
		$this->hasException = false;
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$studyStore = $this->createStub(StudyStore::class);
		$studyStore->method('getStudyIdList')
			->willReturnCallback(function(): array {
				return $this->allStudies;
			});
		$studyStore->method('getStudyConfigAsJson')
			->willReturnCallback(function(int $studyId): string {
				if($this->hasException)
					throw new CriticalError('Unit test exception');
				return $this->allJsons[$studyId];
			});
		$studyStore->method('getStudyLangConfigAsJson')
			->willReturnCallback(function(int $studyId): string {
				if($this->hasException)
					throw new CriticalError('Unit test exception');
				return $this->allLangJsons[$studyId];
			});
		$this->createStoreMock('getStudyStore', $studyStore, $observer);
		
		
		$studyMetadataStore = $this->createStub(StudyMetadataStore::class);
		$studyMetadataStore->method('getAccessKeys')
			->willReturnCallback(function(): array {
				return $this->accessKeys;
			});
		$this->createStoreMock('getStudyMetadataStore', $studyMetadataStore, $observer);
		
		
		$studyAccessIndexStore = $this->createStub(StudyAccessIndexStore::class);
		$studyAccessIndexStore->method('getStudyIds')
			->willReturnCallback(function($key): array {
				return $this->accessKeyIndex[$key];
			});
		$this->createStoreMock('getStudyAccessIndexStore', $studyAccessIndexStore, $observer);
		
		
		$accountStore = $this->createStub(AccountStore::class);
		$accountStore
			->method('getPermissions')
			->willReturnCallback(function(): array {
				return [
					'admin' => $this->isAdmin,
					'write' => $this->writePermissionStudies,
					'read' => $this->readPermissionStudies,
					'msg' => $this->msgPermissionStudies
				];
			});
		$this->createStoreMock('getAccountStore', $accountStore, $observer);
		
		return $observer;
	}
	
	private function arrayToJson(array $a, bool $useLangSource = false): string {
		$output = [];
		$jsons = $useLangSource ? $this->allLangJsons :$this->allJsons;
		foreach($a as $studyId) {
			$output[] = $jsons[$studyId];
		}
		return JsonOutput::successString('['.implode(',', $output).']');
	}
	
	
	function test_without_login_or_access_key() {
		require DIR_BASE .'/api/studies.php';
		$this->expectOutputString($this->arrayToJson($this->accessKeyIndex[''], true));
	}
	
	function test_without_login_but_with_access_key() {
		$this->setGet(['access_key' => 'key1']);
		
		require DIR_BASE .'/api/studies.php';
		$this->expectOutputString($this->arrayToJson($this->accessKeyIndex['key1'], true));
	}
	function test_without_admin_but_logged_in() {
		$this->setGet(['is_loggedIn' => true]);
		Permission::setLoggedIn('user1');
		
		require DIR_BASE .'/api/studies.php';
		$this->expectOutputString($this->arrayToJson([234, 567, 456, 123]));
	}
	function test_asAdmin() {
		$this->setGet(['is_loggedIn' => true]);
		Permission::setLoggedIn('user1');
		$this->isAdmin = true;
		require DIR_BASE .'/api/studies.php';
		$this->expectOutputString($this->arrayToJson($this->allStudies));
	}
	
	function test_with_exception() {
		$this->hasException = true;
		require DIR_BASE .'/api/studies.php';
		$this->expectOutputString(JsonOutput::error('Unit test exception'));
	}
	function test_without_init() {
		$this->assertIsInit('studies');
	}
}