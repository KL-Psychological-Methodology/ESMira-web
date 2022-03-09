<?php

ignore_user_abort(true);
set_time_limit(0);

require_once '../backend/autoload.php';

use backend\Output;



if(!isset($_GET['type']))
	Output::error('No data');

$classIndex = [
	//no permission:
	'data_folder_exists' => 'backend\admin\features\noPermission\DataFolderExists',
	'prep_init_esmira' => 'backend\admin\features\noPermission\InitESMiraPrep',
	'init_esmira' => 'backend\admin\features\noPermission\InitESMira',
	'login' => 'backend\admin\features\noPermission\Login',
	'logout' => 'backend\admin\features\noPermission\Logout',
	'get_permissions' => 'backend\admin\features\noPermission\GetPermissions',
	
	//logged in:
	'change_password' => 'backend\admin\features\loggedIn\ChangePassword',
	'change_username' => 'backend\admin\features\loggedIn\ChangeUsername',
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
	'list_data' => 'backend\admin\features\readPermission\ListData',
	'get_data' => 'backend\admin\features\readPermission\GetData',
	
	//write
	'get_new_id' => 'backend\admin\features\writePermission\GetNewId',
	'is_frozen' => 'backend\admin\features\writePermission\IsFrozen',
	'freeze_study' => 'backend\admin\features\writePermission\IsFrozen',
	'empty_data' => 'backend\admin\features\writePermission\EmptyData',
	'check_changed' => 'backend\admin\features\writePermission\CheckChanged',
	'load_langs' => 'backend\admin\features\writePermission\LoadLangs',
	'backup_study' => 'backend\admin\features\writePermission\BackupStudy',
	'save_study' => 'backend\admin\features\writePermission\SaveStudy',
	'mark_study_as_updated' => 'backend\admin\features\writePermission\MarkStudyAsUpdated',
	
	//admin
	'get_serverConfigs' => 'backend\admin\features\adminPermission\GetServerConfig',
	'save_serverConfigs' => 'backend\admin\features\adminPermission\SaveServerConfigs',
	'list_errors' => 'backend\admin\features\adminPermission\ListErrors',
	'get_error' => 'backend\admin\features\adminPermission\GetError',
	'change_error' => 'backend\admin\features\adminPermission\ChangeError',
	'delete_error' => 'backend\admin\features\adminPermission\DeleteError',
	'delete_study' => 'backend\admin\features\adminPermission\DeleteStudy',
	'list_users' => 'backend\admin\features\adminPermission\ListUsers',
	'create_user' => 'backend\admin\features\adminPermission\CreateUser',
	'delete_user' => 'backend\admin\features\adminPermission\DeleteUser',
	'add_userPermission' => 'backend\admin\features\adminPermission\AddUserPermission',
	'delete_userPermission' => 'backend\admin\features\adminPermission\DeleteUserPermission',
	'toggle_admin' => 'backend\admin\features\adminPermission\ToggleAdmin',
	'check_update' => 'backend\admin\features\adminPermission\CheckUpdate',
	'download_update' => 'backend\admin\features\adminPermission\DownloadUpdate',
	'do_update' => 'backend\admin\features\adminPermission\DoUpdate',
];

$type = $_GET['type'];

if(!isset($classIndex[$type]))
	Output::error("Unexpected request");
$className = $classIndex[$type];
$c = new $className;
$c->exec();