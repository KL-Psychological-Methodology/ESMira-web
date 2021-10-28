<?php
/*
 * 3: Change of variable names
 * 4: introduction of group.internalId and removal of completeRepeatType and timeConstraintType (Android: 2.3.3.12, iOS: 1.0.11
 * 5: introduce dataset tokens & replace specificGroupIndex with specificGroupInternalId (android: 2.3.3.16, iOS: 1.0.14)
 * 6: send accessKey to update.php and statistics.php (Android: 2.4.0.0, iOS: 1.0.17)
 * 7: new chat structure (Android: 2.4.0.0, iOS: 1.0.17)
 * 8: server_version to serverVersion (Android: 2.4.1.0, iOS: 1.1.1)
 * 9: change (form_duration, form_duration, last_notification_formatted, new_schedule, notification_scheduled, notification_scheduled_formatted) and server_version to serverVersion
 * 10: (Android: 2.4.2.0, iOS: 1.1.2):
 * 		dataSet.last_notification > dataSet.lastInvitation
 * 		dataSet.last_notification_formatted > dataSet.lastInvitation_formatted
 * 		dataSet.notification_scheduled > dataSet.actionScheduledTo
 * 		dataSet.notification_scheduled_formatted > dataSet.actionScheduledTo_formatted
 * 		dataSet.groupName > dataSet.questionnaireName
 *
 * 		eventTriggers.skipThisGroup > eventTriggers.skipThisQuestionnaire
 * 		eventTriggers.specificGroupEnabled > eventTriggers.specificQuestionnaireEnabled
 * 		eventTriggers.specificGroupInternalId > eventTriggers.specificQuestionnaireInternalId
 * 		study.group > study.questionnaire
 * 		group.name > group.title
 */


const BACKUP_INTERVAL_DAYS = 30, //in days; interval for backup warning
	NUMBER_OF_SAVED_DAYS_IN_SERVER_STATISTICS = 14,
	MAX_MSGS_PER_USER = 100,
	MAX_USERINPUT_LENGTH = 2000,
	MAX_POSSIBLE_BACKUPS_PER_DAY = 1000, //should never be reached. Mainly there to prevent too many backups in case of a bug
	MAX_FILESIZE_FOR_CHANGES = 1000000000, //1gb; when variables are changed, lines will be adjusted. If file is too big, a new file will be created instead (after backup of course!)
	DATASET_SERVER_TIMEOUT = 1000, //milliseconds between received datasets (per user) or the server will decline them (excludes quit event)
	STATISTICS_TIMED_STORAGE_MAX_ENTRIES = 2000, //higher numbers leads to bigger files for statistics and higher performance impact when adding new values too statistics (and can also lag the statistic charts)
	STATISTICS_MAX_NEW_ENTRIES_AT_ONCE = 1500,
	ONE_DAY = 86400, //in seconds: 60*60*24
	SMALLEST_TIMED_DISTANCE = 675, //11min in seconds, needs to multiply into ONE_DAY. Will be doubled when there are more entries than STATISTICS_TIMED_STORAGE_MAX_ENTRIES. All values inside this timeframe will be merged. A higher timeframe decreases the number of data that has to be saved into statistics
	
	COOKIE_LAST_COMPLETED = 'last_completed%1$d_%2$d',
	URL_ABOUT_ESMIRA_HOST = 'esmira.kl.ac.at',
	URL_ABOUT_ESMIRA_JSON_LOCATION = '/documents/about/langs/%s.json',
	
	CONDITION_TYPE_ALL = 0,
	CONDITION_TYPE_AND = 1,
	CONDITION_TYPE_OR = 2,
	
	CONDITION_OPERATOR_EQUAL = 0,
	CONDITION_OPERATOR_UNEQUAL = 1,
	CONDITION_OPERATOR_GREATER = 2,
	CONDITION_OPERATOR_LESS = 3,

	STATISTICS_STORAGE_TYPE_TIMED = 0,
	STATISTICS_STORAGE_TYPE_FREQ_DISTR = 1,
	
	STATISTICS_CHARTTYPES_LINE = 0,
	STATISTICS_CHARTTYPES_LINE_FILLED = 1,
	STATISTICS_CHARTTYPES_BARS = 2,
	STATISTICS_CHARTTYPES_PIE = 3,
	
	STATISTICS_DATATYPES_DAILY = 0,
	STATISTICS_DATATYPES_FREQ_DISTR = 1,
	STATISTICS_DATATYPES_SUM = 2,
	STATISTICS_DATATYPES_XY = 3,
	
	//CSV-Options:
	CSV_DELIMITER = ';',
	
	ERROR_FILE_EXTENSION = '.txt',
	FILENAME_HTACCESS = '.htaccess',
	
	DATASET_TYPE_JOINED = 'joined',
	DATASET_TYPE_QUIT = 'quit',
	DATASET_TYPE_QUESTIONNAIRE = 'questionnaire',
	
	//basic keys which are included in every questionnaire dataset
	KEYS_QUESTIONNAIRE_BASE_RESPONSES = [
		'userId',
		'uploaded',
		'appType',
		'appVersion',
		'studyId',
		'accessKey',
		'studyVersion',
        'studyLang',
		'questionnaireName',
		'eventType',
		'timezone',
		'responseTime',
		'responseTime_formatted',
		'formDuration',
		'lastInvitation',
		'lastInvitation_formatted' //will be created by the server
	],
	
	//all event-related keys that are included in the event file:
	KEYS_EVENT_RESPONSES = [
		'userId',
		'uploaded',
		'appType',
		'appVersion',
		'studyId',
		'accessKey',
		'studyVersion',
        'studyLang',
		'questionnaireName',
		'eventType',
		'timezone',
		'responseTime',
		'responseTime_formatted',
		'newSchedule',
		'actionScheduledTo',
		'actionScheduledTo_formatted', //will be created by the server
		'model',
		'osVersion',
		'manufacturer'
	],
	
	KEYS_WEB_ACCESS = [
		'responseTime',
		'page',
		'referer',
		'user_agent',
	],
	
	//templates:
	HTACCESS_RESPONSES_TEMPLATE = 'Options +Indexes
AuthType Basic
%1$s',
HTACCESS_MAIN_TEMPLATE = '<Files ~ "^\..+">
  Order Allow,Deny
  Deny from all
</Files>
Options -Indexes
AuthType Basic
AuthName "Password Protected Area"
AuthUserFile %1$s
Require valid-user',
	HTACCESS_REQUIRE_LINE = 'Require user %1$s
',
DEFAULT_SERVER_SETTINGS = array(
	'serverName' => array ('_' => '')
);;

?>