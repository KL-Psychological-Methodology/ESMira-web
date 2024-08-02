<?php


/*
Reason for update:
    3.3.0 introduces Scripting with the Merlin scripting language.
    Part of this feature is a functionality to receive user logs and error messages from clients on the server.
    This script updates the folder structure so that logs can be stored in the file system. 
*/

use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

$handle = opendir(PathsFS::folderStudies());
while($studyId = readdir($handle)) {
	if($studyId[0] === '.' || $studyId === PathsFS::FILENAME_STUDY_INDEX)
		continue;
	$path = PathsFS::folderMerlinLogs($studyId);
	FileSystemBasics::createFolder($path);
}