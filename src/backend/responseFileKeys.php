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
	'responseTime',
	'responseTime_formatted', //will be created by the server
	'formDuration',
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
	'responseTime',
	'responseTime_formatted', //will be created by the server
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