<?php

namespace test\backend\admin\features\adminPermission;

use backend\admin\features\adminPermission\CheckUpdate;
use backend\Configs;
use backend\FileSystemBasics;
use test\testConfigs\BaseAdminPermissionTestSetup;

require_once __DIR__ . '/../../../../../backend/autoload.php';
require_once __DIR__ .'/../../../../testConfigs/variables.php';

class CheckUpdateTest extends BaseAdminPermissionTestSetup {
	private static $changelogContent = 'changelog';
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if(!file_exists(TEST_DATA_FOLDER))
			mkdir(TEST_DATA_FOLDER);
		file_put_contents(TEST_DATA_FOLDER .'changelog.txt', self::$changelogContent);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
		rmdir(TEST_DATA_FOLDER);
	}
	
	private function createNewVersionArray(array $a, int $diff): array {
		$i = random_int(0, 2);
		$a[$i] += $diff;
		for(++$i; $i <= 2; ++$i) {
			$a[$i] = random_int(1, 9);
		}
		return $a;
	}
	private function versionToString(array $versionArray): string {
		return implode('.', $versionArray);
	}
	private function createVersionFile(string $branch, int $diff): array {
		parent::setUpBeforeClass();
		
		$currentVersionArray = [
			random_int(1, 9),
			random_int(1, 9),
			random_int(1, 9)
		];
		$otherVersionArray = $this->createNewVersionArray($currentVersionArray, $diff);
		$otherVersion = $this->versionToString($otherVersionArray);
		Configs::injectConfig('configs.checkUpdate.injected.php');
		
		$path = sprintf(Configs::get('url_update_packageInfo'), $branch);
		file_put_contents($path, json_encode(['version' => $otherVersion]));
		
		return [$this->versionToString($currentVersionArray), $otherVersion];
	}
	
	private function doTest(bool $preRelease, bool $hasUpdate) {
		list($currentVersion, $otherVersion) = $this->createVersionFile($preRelease ? 'develop' : 'main', $hasUpdate ? +1 : -1);
		
		$this->setGet([
			'version' => $currentVersion,
			'preRelease' => $preRelease
		]);
		$obj = new CheckUpdate();
		$this->assertEquals(
			$hasUpdate
				? ['has_update' => true, 'newVersion' => $otherVersion, 'changelog' => self::$changelogContent]
				: ['has_update' => false],
			$obj->exec(),
			"Unexpected output with current version $currentVersion and update version $otherVersion"
		);
	}
	
	function test_release() {
		$this->doTest(false, true);
	}
	function test_preRelease() {
		$this->doTest(true, true);
	}
	function test_release_with_smaller_version() {
		$this->doTest(true, false);
	}
	function test_preRelease_with_smaller_version() {
		$this->doTest(false, false);
	}
}