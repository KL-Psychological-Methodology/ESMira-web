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
	'data_folder_exists' => 'backend\admin\features\noPermission\DataFolderExists',
	'init_esmira_prep' => 'backend\admin\features\noPermission\InitESMiraPrep',
	'init_esmira' => 'backend\admin\features\noPermission\InitESMira',
	'login' => 'backend\admin\features\noPermission\Login',
	'logout' => 'backend\admin\features\noPermission\Logout',
	'get_permissions' => 'backend\admin\features\noPermission\GetPermissions',
	
	//logged in:
	'change_password' => 'backend\admin\features\loggedIn\ChangePassword',
	'change_accountName' => 'backend\admin\features\loggedIn\ChangeAccountName',
	'get_tokenList' => 'backend\admin\features\loggedIn\GetTokenList',
	'get_loginHistory' => 'backend\admin\features\loggedIn\GetLoginHistory',
	'remove_token' => 'backend\admin\features\loggedIn\RemoveToken',
	
	//msg:
	'list_participants' => 'backend\admin\features\messagePermission\ListParticipants',
	'list_messages' => 'backend\admin\features\messagePermission\ListMessages',
	'list_userWithMessages' => 'backend\admin\features\messagePermission\ListUserWithMessages',
	'messages_setRead' => 'backend\admin\features\messagePermission\MessageSetRead',
	'send_message' => 'backend\admin\features\messagePermission\SendMessage',
	'delete_message' => 'backend\admin\features\messagePermission\DeleteMessage',
	
	//read:
	'validate_reward_code' => 'backend\admin\features\readPermission\ValidateRewardCode',
	'get_reward_code_data' => 'backend\admin\features\readPermission\GetRewardCodeData',
	'list_data' => 'backend\admin\features\readPermission\ListData',
	'get_data' => 'backend\admin\features\readPermission\GetData',
	'create_mediaZip' => 'backend\admin\features\readPermission\CreateMediaZip',
	'get_media' => 'backend\admin\features\readPermission\GetMedia',
	
	//write
	'is_frozen' => 'backend\admin\features\writePermission\IsFrozen',
	'delete_study' => 'backend\admin\features\writePermission\DeleteStudy',
	'freeze_study' => 'backend\admin\features\writePermission\FreezeStudy',
	'get_new_id' => 'backend\admin\features\writePermission\GetNewId',
	'empty_data' => 'backend\admin\features\writePermission\EmptyData',
	'check_changed' => 'backend\admin\features\writePermission\CheckChanged',
	'load_langs' => 'backend\admin\features\writePermission\LoadLangs',
	'backup_study' => 'backend\admin\features\writePermission\BackupStudy',
	'save_study' => 'backend\admin\features\writePermission\SaveStudy',
	'mark_study_as_updated' => 'backend\admin\features\writePermission\MarkStudyAsUpdated',
	
	//create
	'create_study' => 'backend\admin\features\createPermission\CreateStudy',
	
	//admin
	'get_last_activities' => 'backend\admin\features\adminPermission\GetLastActivities',
	'get_serverConfigs' => 'backend\admin\features\adminPermission\GetServerConfig',
	'save_serverConfigs' => 'backend\admin\features\adminPermission\SaveServerConfigs',
	'list_errors' => 'backend\admin\features\adminPermission\ListErrors',
	'get_error' => 'backend\admin\features\adminPermission\GetError',
	'change_error' => 'backend\admin\features\adminPermission\ChangeError',
	'delete_error' => 'backend\admin\features\adminPermission\DeleteError',
	'list_accounts' => 'backend\admin\features\adminPermission\ListAccounts',
	'create_account' => 'backend\admin\features\adminPermission\CreateAccount',
	'delete_account' => 'backend\admin\features\adminPermission\DeleteAccount',
	'add_studyPermission' => 'backend\admin\features\adminPermission\AddStudyPermission',
	'delete_studyPermission' => 'backend\admin\features\adminPermission\DeleteStudyPermission',
	'toggle_accountPermission' => 'backend\admin\features\adminPermission\ToggleAccountPermission',
	'download_update' => 'backend\admin\features\adminPermission\DownloadUpdate',
	'do_update' => 'backend\admin\features\adminPermission\DoUpdate',
	'update_version' => 'backend\admin\features\adminPermission\UpdateVersion', //not used in production
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
