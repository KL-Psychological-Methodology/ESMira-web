<?php

//basic keys which are included in every questionnaire dataset
const KEYS_QUESTIONNAIRE_BASE_RESPONSES = [
	'entryId',
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
	'timezoneOffset',
	'responseTime',
	'responseTime_formatted', //will be created by the server
	'localDateTime', //will be sent by the client
	'formDuration',
	'pageDurations',
	'lastInvitation',
	'lastInvitation_formatted' //will be created by the server
];

//all event-related keys that are included in the event file:
const KEYS_EVENT_RESPONSES = [
	'entryId',
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
	'timezoneOffset',
	'responseTime',
	'responseTime_formatted', //will be created by the server
	'localDateTime', //will be sent by the client
	'newSchedule',
	'actionScheduledTo',
	'actionScheduledTo_formatted', //will be created by the server
	'model',
	'osVersion',
	'manufacturer'
];
const KEYS_WEB_ACCESS = [
	'responseTime',
	'page',
	'referer',
	'userAgent',
];
