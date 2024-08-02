<?php
ignore_user_abort(true);
set_time_limit(0);

require_once dirname(__FILE__, 2) . '/backend/autoload.php';

use backend\exceptions\CriticalException;
use backend\FileUploader;
use backend\JsonOutput;
use backend\Configs;

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}
if(!Configs::getDataStore()->isReady()) {
	echo JsonOutput::error('Server is not ready.');
	return;
}

if(intval($_SERVER['CONTENT_LENGTH']) > 0 && empty($_POST)) {
	echo JsonOutput::error('File is too big for server');
	return;
}

if(!isset($_POST['studyId']) || !isset($_POST['userId']) || !isset($_POST['dataType'])) {
	echo JsonOutput::error('Missing data');
	return;
}


$studyId = (int)$_POST['studyId'];
$userId = $_POST['userId'];
$dataType = $_POST['dataType'];

//get fileData:

if(!isset($_FILES['upload'])) {
	echo JsonOutput::error('No content to upload');
	return;
}


$fileData = $_FILES["upload"];


//get identifier:

if(!isset($fileData['name'])) {
	echo JsonOutput::error('No content information');
	return;
}


$identifier = (int)$fileData['name'];


//check size:

if($fileData['size'] > Configs::get('max_filesize_for_uploads')) {
	echo JsonOutput::error('File is too big for settings');
	return;
}


//check type:

switch($dataType) {
	case 'Image':
		try {
			if(empty($fileData['tmp_name']))
				throw new CriticalException('tmp_name is faulty. The file might be too big?');
			if(!getimagesize($fileData['tmp_name']))
				throw new CriticalException('getimagesize() failed');
		} catch(Throwable $e) {
			echo JsonOutput::error('Not an image');
			return;
		}
		
		break;
	case 'Audio':
		if(!preg_match('/video/i', mime_content_type($fileData['tmp_name']))) {
			echo JsonOutput::error('Wrong format');
			return;
		}
		break;
	default:
		echo JsonOutput::error('Unknown type');
		return;
}

try {
	Configs::getDataStore()->getResponsesStore()->uploadFile($studyId, $userId, $identifier, new FileUploader($fileData));
} catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
	return;
}

echo JsonOutput::successObj();