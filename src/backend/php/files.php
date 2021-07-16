<?php
const FOLDER_DATA = 'data/',
	FOLDER_ERRORS = FOLDER_DATA.'errors/',
	FOLDER_STUDIES = FOLDER_DATA.'studies/',
	FOLDER_TOKEN = FOLDER_DATA.'.loginToken/',
	FILENAME_STATISTICS_METADATA = '.metadata',
	FILENAME_STATISTICS_NEWLINES = '.new_data',
	FILENAME_STATISTICS_JSONFILE = 'json',
	FILENAME_STUDY_INDEX = '.index',
	FILENAME_WEB_ACCESS = 'web_access',
	FILENAME_EVENTS = 'events',

	FILE_STUDY_INDEX = FOLDER_STUDIES.FILENAME_STUDY_INDEX,
	FILE_LOGINS = FOLDER_DATA.'.logins',
	FILE_SERVER_NAME = FOLDER_DATA.'.server_name',
	FILE_SERVER_SETTINGS = FOLDER_DATA.'.server_settings.php',
	FILE_PERMISSIONS = FOLDER_DATA.'.permissions',
	FILE_SERVER_STATISTICS = FOLDER_DATA.'server_statistics.json',
	FILE_PRIVACY_POLICY = FOLDER_DATA.'privacy_policy.html',
	FILE_IMPRESSUM = FOLDER_DATA.'impressum.html';


function get_folder_study($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES ."$study_id/";
}
function get_folder_messages($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES ."$study_id/.messages/";
}
function get_folder_messages_archive($study_id) {
	return get_folder_messages($study_id) ."/.archive/";
}
function get_folder_messages_pending($study_id) {
	return get_folder_messages($study_id) ."/.pending/";
}
function get_folder_messages_unread($study_id) {
	return get_folder_messages($study_id) ."/.unread/";
}
function get_folder_responsesIndex($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES ."$study_id/.responses_index/";
}
function get_folder_responses($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES ."$study_id/responses/";
}
function get_folder_statistics($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES ."$study_id/.statistics/";
}
function get_folder_userData($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES ."$study_id/.userdata/";
}
function get_folder_token($user) {
	return FOLDER_TOKEN .make_urlFriendly($user).'/';
}


function get_file_studyConfig($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES."$study_id/.config.json";
}
function get_file_studyMetadata($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES."$study_id/.metadata";
}
function get_file_responses($study_id, $questionnaire_identifier) {
	return get_folder_responses($study_id) ."$questionnaire_identifier.csv";
}
function get_file_responsesIndex($study_id, $questionnaire_identifier) {
	return get_folder_responsesIndex($study_id) .$questionnaire_identifier;
}
function get_file_responsesBackup($study_id, $questionnaire_identifier) {
	$date = date('o-m-d');
	$folder = get_folder_responses($study_id);
	$filename = "{$date}_$questionnaire_identifier";
	$file = $folder ."$filename.csv";


	$count = 2;
	while(file_exists($file)) {
		$file = "$folder{$date}_{$count}_$questionnaire_identifier.csv";
		if(++$count > MAX_POSSIBLE_BACKUPS_PER_DAY)
			error('Could not rename old datafile. There are too many copies. Aborting... Check your datafiles before trying again.');
	}

	return $file;
}
function get_file_statisticsMetadata($study_id) {
	return get_folder_statistics($study_id).FILENAME_STATISTICS_METADATA;
}
function get_file_statisticsNewData($study_id) {
	return get_folder_statistics($study_id).FILENAME_STATISTICS_NEWLINES;
}
function get_file_statisticsJson($study_id) {
	return get_folder_statistics($study_id).FILENAME_STATISTICS_JSONFILE;
}
function get_file_userData($study_id, $user) {
	return get_folder_userData($study_id) .make_urlFriendly($user);
}
function get_file_message_pending($study_id, $user) {
	return get_folder_messages_pending($study_id) .make_urlFriendly($user);
}
function get_file_message_archive($study_id, $user) {
	return get_folder_messages_archive($study_id) .make_urlFriendly($user);
}
function get_file_message_unread($study_id, $user) {
	return get_folder_messages_unread($study_id) .make_urlFriendly($user);
}
function get_file_lock($study_id) {
	$study_id = (int) $study_id;
	return FOLDER_STUDIES."$study_id/.locked";
}
function get_file_token($user, $hash) {
	return get_folder_token($user) .$hash;
}
function get_file_tokenHistory($user, $num) {
	return get_folder_token($user) .'.history' .((int) $num);
}
function get_file_blockLogin($user) {
	return get_folder_token($user) .".blocking";
}


//For make_urlFriendly() and get_urlFriendly(), thanks to https://www.php.net/manual/en/function.base64-encode.php#123098
function make_urlFriendly($s) {
	return str_replace(['+','/','='], ['-','_',''], base64_encode($s));
//	return strstr(base64_encode($s), '+/=', '-_~');
}
function get_urlFriendly($s) {
	return base64_decode(str_replace(['-','_'], ['+','/'], $s));
//	return base64_decode(strstr($s, '-_~', '+/='));
}
?>