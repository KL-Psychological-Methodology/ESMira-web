<?php
use backend\fileSystem\loader\StudyAccessKeyIndexLoader;

//index numbers in ~open array got messed up. We have to fix them:
$studyIndex = StudyAccessKeyIndexLoader::importFile();
$newArray = [];
foreach($studyIndex['~open'] as $studyId) {
	$newArray[] = $studyId;
}
$studyIndex['~open'] = $newArray;
StudyAccessKeyIndexLoader::exportFile($studyIndex);