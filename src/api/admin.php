<?php

ignore_user_abort(true);
set_time_limit(0);

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\JsonOutput;


if(!isset($_GET['type'])) {
	echo JsonOutput::error('Missing data');
	return;
}

$classIndex = [
	//no permission:
	'DataFolderExists' => 'backend\admin\features\noPermission\DataFolderExists',
	'InitESMiraPrep' => 'backend\admin\features\noPermission\InitESMiraPrep',
	'InitESMira' => 'backend\admin\features\noPermission\InitESMira',
	'login' => 'backend\admin\features\noPermission\Login',
	'logout' => 'backend\admin\features\noPermission\Logout',
	'GetPermissions' => 'backend\admin\features\noPermission\GetPermissions',
	
	//logged in:
	'GetStrippedStudyList' => 'backend\admin\features\loggedIn\GetStrippedStudyList',
	'GetStudyFromQuestionnaireId' => 'backend\admin\features\loggedIn\GetStudyFromQuestionnaireId',
	'GetFullStudy' => 'backend\admin\features\loggedIn\GetFullStudy',
	'ChangePassword' => 'backend\admin\features\loggedIn\ChangePassword',
	'ChangeAccountName' => 'backend\admin\features\loggedIn\ChangeAccountName',
	'GetTokenList' => 'backend\admin\features\loggedIn\GetTokenList',
	'GetLoginHistory' => 'backend\admin\features\loggedIn\GetLoginHistory',
	'RemoveToken' => 'backend\admin\features\loggedIn\RemoveToken',
	'GetBookmarks' => 'backend\admin\features\loggedIn\GetBookmarks',
	'SetBookmark' => 'backend\admin\features\loggedIn\SetBookmark',
	'DeleteBookmark' => 'backend\admin\features\loggedIn\DeleteBookmark',
	
	//msg:
	'ListParticipants' => 'backend\admin\features\messagePermission\ListParticipants',
	'ListMessages' => 'backend\admin\features\messagePermission\ListMessages',
	'ListUserWithMessages' => 'backend\admin\features\messagePermission\ListUserWithMessages',
	'MessageSetRead' => 'backend\admin\features\messagePermission\MessageSetRead',
	'SendMessage' => 'backend\admin\features\messagePermission\SendMessage',
	'DeleteMessage' => 'backend\admin\features\messagePermission\DeleteMessage',
	
	//read:
	'ValidateRewardCode' => 'backend\admin\features\readPermission\ValidateRewardCode',
	'GetRewardCodeData' => 'backend\admin\features\readPermission\GetRewardCodeData',
	'ListData' => 'backend\admin\features\readPermission\ListData',
	'GetData' => 'backend\admin\features\readPermission\GetData',
	'CreateMediaZip' => 'backend\admin\features\readPermission\CreateMediaZip',
	'GetMediaZip' => 'backend\admin\features\readPermission\GetMediaZip',
	'GetMedia' => 'backend\admin\features\readPermission\GetMedia',
	'ListMerlinLogs' => 'backend\admin\features\readPermission\ListMerlinLogs',
	'DeleteMerlinLog' => 'backend\admin\features\readPermission\DeleteMerlinLog',
	'ChangeMerlinLog' => 'backend\admin\features\readPermission\ChangeMerlinLog',
	'GetMerlinLog' => 'backend\admin\features\readPermission\GetMerlinLog',
	
	//write
	'IsFrozen' => 'backend\admin\features\writePermission\IsFrozen',
	'DeleteStudy' => 'backend\admin\features\writePermission\DeleteStudy',
	'FreezeStudy' => 'backend\admin\features\writePermission\FreezeStudy',
	'GetNewId' => 'backend\admin\features\writePermission\GetNewId',
	'EmptyData' => 'backend\admin\features\writePermission\EmptyData',
	'CheckChanged' => 'backend\admin\features\writePermission\CheckChanged',
	'BackupStudy' => 'backend\admin\features\writePermission\BackupStudy',
	'SaveStudy' => 'backend\admin\features\writePermission\SaveStudy',
	'MarkStudyAsUpdated' => 'backend\admin\features\writePermission\MarkStudyAsUpdated',
	
	//create
	'CreateStudy' => 'backend\admin\features\createPermission\CreateStudy',
	
	//admin
	'GetLastActivities' => 'backend\admin\features\adminPermission\GetLastActivities',
	'GetUsedSpacePerStudy' => 'backend\admin\features\adminPermission\GetUsedSpacePerStudy',
	'GetServerConfig' => 'backend\admin\features\adminPermission\GetServerConfig',
	'SaveServerConfigs' => 'backend\admin\features\adminPermission\SaveServerConfigs',
	'ListErrors' => 'backend\admin\features\adminPermission\ListErrors',
	'GetError' => 'backend\admin\features\adminPermission\GetError',
	'ChangeError' => 'backend\admin\features\adminPermission\ChangeError',
	'DeleteError' => 'backend\admin\features\adminPermission\DeleteError',
	'ListAccounts' => 'backend\admin\features\adminPermission\ListAccounts',
	'CreateAccount' => 'backend\admin\features\adminPermission\CreateAccount',
	'DeleteAccount' => 'backend\admin\features\adminPermission\DeleteAccount',
	'AddStudyPermission' => 'backend\admin\features\adminPermission\AddStudyPermission',
	'DeleteStudyPermission' => 'backend\admin\features\adminPermission\DeleteStudyPermission',
	'ToggleAccountPermission' => 'backend\admin\features\adminPermission\ToggleAccountPermission',
	'DownloadUpdate' => 'backend\admin\features\adminPermission\DownloadUpdate',
	'DoUpdate' => 'backend\admin\features\adminPermission\DoUpdate',
	'UpdateVersion' => 'backend\admin\features\adminPermission\UpdateVersion', //not used in production
];

$type = $_GET['type'];

if(!isset($classIndex[$type])) {
	echo JsonOutput::error('Unexpected request');
	return;
}
try {
	$className = $classIndex[$type];
	$c = new $className;
	$c->execAndOutput();
}
catch(CriticalException $e) {
	echo JsonOutput::error($e->getMessage());
}
catch(PageFlowException $e) {
	echo JsonOutput::error($e->getMessage());
}
