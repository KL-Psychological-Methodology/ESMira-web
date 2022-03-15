<?php
ignore_user_abort(true);
set_time_limit(0);

require_once '../backend/autoload.php';

use backend\Base;
use backend\Output;
use backend\Files;
use backend\Configs;

if(!isset($_POST['studyId']) || !isset($_POST['userId']) || !isset($_POST['dataType']))
	Output::error('Missing data');

$study_id = (int) $_POST['studyId'];
$userId = (int) $_POST['userId'];
$dataType = $_POST['dataType'];

//get fileData:

if(!isset($_FILES['upload']))
	Output::error('No content to upload');

$fileData = $_FILES["upload"];


//get identifier:

if(!isset($fileData['name']))
	Output::error('No content to upload');

$identifier = $fileData['name'];


//check size:

if ($fileData['size'] > Configs::get('max_filesize_for_uploads')) {
	Output::error('File is too big');
	$uploadOk = 0;
}


//check type:

switch($dataType) {
	case 'Image':
		if(!getimagesize($fileData['tmp_name']))
			Output::error('Not an image');
		break;
	default:
		Output::error('Unknown type');
}


//get target location info:

$waiting_file = Files::get_file_pendingUploads($study_id, $userId, $identifier);
if(!file_exists($waiting_file))
	Output::error('Not allowed' .$waiting_file);

$target_path = file_get_contents($waiting_file);
if(!$target_path)
	Output::error('Internal server error');


if(file_exists($target_path))
	Output::error('File already exists');


//upload:

if(!move_uploaded_file($fileData["tmp_name"], $target_path) || !unlink($waiting_file))
	Output::error('Uploading failed');


// delete zipped folder if it exists
$file_mediaZip = Files::get_file_mediaZip($study_id);

if(file_exists($file_mediaZip))
	unset($file_mediaZip);

Output::successObj();