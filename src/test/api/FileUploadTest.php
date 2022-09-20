<?php

namespace test\api;

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\FileSystemBasics;
use backend\FileUploader;
use backend\JsonOutput;
use backend\subStores\ResponsesStore;
use PHPUnit\Framework\MockObject\Stub;
use test\testConfigs\BaseApiTestSetup;

require_once __DIR__ .'/../../backend/autoload.php';
require_once __DIR__ .'/../testConfigs/variables.php';

class FileUploadTest extends BaseApiTestSetup {
	private $studyId = 123;
	private $userid = 'userId';
	private $identifier = 10000;
	private $hasUploadException = false;
	private $path = TEST_DATA_FOLDER .'uploaded.png';
	
	protected function tearDown(): void {
		parent::tearDown();
		$this->hasUploadException = false;
		FileSystemBasics::emptyFolder(TEST_DATA_FOLDER);
	}
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		rmdir(TEST_DATA_FOLDER);
	}
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		mkdir(TEST_DATA_FOLDER);
	}
	
	protected function setUpDataStoreObserver(): Stub {
		$observer = parent::setUpDataStoreObserver();
		
		$responsesStore = $this->createDataMock(ResponsesStore::class, 'uploadFile', function() {
			if($this->hasUploadException)
				throw new CriticalException('Unit test exception');
		});
		$this->createStoreMock('getResponsesStore', $responsesStore, $observer);
		
		return $observer;
	}
	
	
	private function doPreparations() {
		$_SERVER['CONTENT_LENGTH'] = 10;
		$_FILES['upload'] = [
			'tmp_name' => $this->path,
			'name' => $this->identifier,
			'size' => Configs::get('max_filesize_for_uploads')
		];
		$this->setPost([
			'studyId' => $this->studyId,
			'userId' => $this->userid,
			'dataType' => 'Image'
		]);
	}
	
	function createImage() {
		$image = imagecreate(1,1);
		$black = imagecolorallocate($image, 0, 0, 0);
		imagefill($image, 1, 1, $black);
		imagepng($image, $this->path);
	}
	
	function test() {
		$this->doPreparations();
		
		$this->createImage();
		
		require DIR_BASE .'/api/file_uploads.php';
		$this->assertDataMock('uploadFile', [$this->studyId, $this->userid, $this->identifier, new FileUploader($_FILES['upload'])]);
		$this->expectOutputString(JsonOutput::successObj());
	}
	
	function test_with_upload_excepion() {
		$this->doPreparations();
		
		$this->createImage();
		
		$this->hasUploadException = true;
		require DIR_BASE .'/api/file_uploads.php';
		$this->expectOutputString(JsonOutput::error('Unit test exception'));
	}
	
	function test_with_faulty_image() {
		$this->doPreparations();
		file_put_contents($this->path, 'notAndImage');
		
		
		require DIR_BASE .'/api/file_uploads.php';
		$this->expectOutputString(JsonOutput::error('Not an image'));
	}
	
	function test_with_unknown_data_type() {
		$this->doPreparations();
		$_POST['dataType'] = 'unknown';
		require DIR_BASE .'/api/file_uploads.php';
		$this->expectOutputString(JsonOutput::error('Unknown type'));
	}
	
	function test_when_file_is_too_big() {
		$this->doPreparations();
		$_FILES['upload']['size'] = Configs::get('max_filesize_for_uploads') + 1;
		require DIR_BASE .'/api/file_uploads.php';
		$this->expectOutputString(JsonOutput::error('File is too big'));
	}
	
	function test_without_fileInfo() {
		$this->doPreparations();
		$_FILES['upload'] = [];
		require DIR_BASE .'/api/file_uploads.php';
		$this->expectOutputString(JsonOutput::error('No content information'));
	}
	
	function test_without_file() {
		$this->doPreparations();
		$_FILES = [];
		require DIR_BASE .'/api/file_uploads.php';
		$this->expectOutputString(JsonOutput::error('No content to upload'));
	}
	
	function test_when_file_too_big() {
		$this->doPreparations();
		$this->setPost(); //php removes post data when file is too big
		
		require DIR_BASE .'/api/file_uploads.php';
		$this->expectOutputString(JsonOutput::error('File is too big'));
	}
	
	function test_with_missing_data() {
		$_SERVER['CONTENT_LENGTH'] = 10;
		$this->assertMissingDataForApi(
			[
				'studyId' => $this->studyId,
				'userId' => 'userId',
				'dataType' => 'Image'
			],
			'file_uploads'
		);
	}
	
	function test_without_init() {
		$this->assertIsInit('file_uploads');
	}
}