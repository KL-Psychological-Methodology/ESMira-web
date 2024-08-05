<?php

namespace test\backend\admin\features\adminPermission;

use backend\MigrationManager;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';

class UpdateVersionTest extends BaseAdminPermissionTestSetup {
	
	function test() {
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3', '1.2.4'));
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3', '1.3.2'));
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3', '2.3.3'));
		
		$this->assertFalse(MigrationManager::testVersionCheck('1.2.3', '1.2.3'));
		$this->assertFalse(MigrationManager::testVersionCheck('1.2.3', '1.1.4'));
		$this->assertFalse(MigrationManager::testVersionCheck('2.2.3', '1.3.3'));
		
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.2.3'));
		$this->assertFalse(MigrationManager::testVersionCheck('1.2.3', '1.2.3-alpha.4'));
		
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.2.3-alpha.5'));
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.2.4-alpha.3'));
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.3.3-alpha.2'));
		$this->assertTrue(MigrationManager::testVersionCheck('1.2.3-alpha.4', '2.2.2-alpha.4'));
		
		$this->assertFalse(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.2.3-alpha.4'));
		$this->assertFalse(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.2.3-alpha.3'));
		$this->assertFalse(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.2.2-alpha.5'));
		$this->assertFalse(MigrationManager::testVersionCheck('1.2.3-alpha.4', '1.1.4-alpha.5'));
		$this->assertFalse(MigrationManager::testVersionCheck('2.2.3-alpha.4', '1.1.4-alpha.5'));
	}
}