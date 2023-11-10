<?php

use backend\dataClasses\ErrorReportInfo;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\Main;

$folderErrorReports = PathsFS::folderErrorReports();
$handle = opendir($folderErrorReports);
$path = $folderErrorReports .".error_info"; //we cannot use ErrorReportInfoLoader::importFile because we are still using the old version of PathFS
$errorInfoArray = file_exists($path) ? unserialize(file_get_contents($path)) : []; //expected to be []; if data is already in new format we just resave everything
while($filename = readdir($handle)) {
	if($filename[0] == '.')
		continue;
	
	$timestamp = (int) $filename;
	
	if(isset($errorInfoArray[$timestamp]))
		continue;
	$oldErrorInfoPath = "$folderErrorReports.$filename.info";
	
	if(file_exists($oldErrorInfoPath)) {
		$errorInfoArray[$timestamp] = unserialize(file_get_contents($oldErrorInfoPath));
		unlink($oldErrorInfoPath);
	}
	else
		$errorInfoArray[$timestamp] = new ErrorReportInfo($timestamp);
}
FileSystemBasics::writeFile($path, serialize($errorInfoArray));
closedir($handle);


//prevent automatic logout:
if(isset($_COOKIE['user'])) {
	Main::setCookie('account', $_COOKIE['user']);
	Main::deleteCookie('user');
}
if(isset($_SESSION['user']))
	$_SESSION['account'] = $_SESSION['user'];