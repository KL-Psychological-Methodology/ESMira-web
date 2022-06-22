export const PageIndex = {
	about:				{filename: "about",				permissions: false},
	account:			{filename: "account",			permissions: ["*"]},
	admin:				{filename: "admin_home",		permissions: ["*"]},
	alarms:				{filename: "alarms",			permissions: ["write"]},
	appInstall:			{filename: "app_install",		permissions: false},
	attend:				{filename: "attend",			permissions: false},
	chart:				{filename: "chart",				permissions: ["write", "read"]},
	charts:				{filename: "charts",			permissions: ["write"]},
	chartEdit:			{filename: "chart_edit",		permissions: ["write", "read"]},
	consent:			{filename: "consent",			permissions: false},
	dataList:			{filename: "data_list",			permissions: ["read"]},
	dateRange:			{filename: "date_range",		permissions: ["write"]},
	dataView:			{filename: "data_view",			permissions: ["*"]},
	dataStatistics:		{filename: "data_statistics",	permissions: ["read"]},
	errorList:			{filename: "error_list",		permissions: []},
	errorView:			{filename: "error_view",		permissions: []},
	init_esmira:		{filename: "init_esmira",		permissions: false},
	input:				{filename: "input",				permissions: ["write"]},
	langGroups:			{filename: "lang_groups",		permissions: ["write"]},
	legal:				{filename: "legal",				permissions: false},
	login:				{filename: "login",				permissions: false},
	messages:			{filename: "messages",			permissions: ["msg"]},
	msg:				{filename: "msg",				permissions: ["msg"]},
	pageSettings:		{filename: "page_settings",		permissions: ["write"]},
	participant:		{filename: "participant",		permissions: false},
	publish:			{filename: "publish",			permissions: ["write"]},
	qEdit:				{filename: "questionnaire_edit",permissions: ["write"]},
	settings:			{filename: "settings",			permissions: false},
	screenshots:		{filename: "screenshots",		permissions: false},
	serverStatistics:	{filename: "server_statistics",	permissions: false},
	source:				{filename: "source",			permissions: ["write"]},
	statistics:			{filename: "statistics",		permissions: false},
	studies:			{filename: "studies",			permissions: false},
	studyEdit:			{filename: "study_edit",		permissions: ["write"]},
	studyDesc:			{filename: "study_description",	permissions: ["write"]},
	studySettings:		{filename: "study_settings",	permissions: ["write"]},
	sumStudy:			{filename: "summary_study",		permissions: ["read"]},
	sumScores:			{filename: "sumscores",			permissions: ["write"]},
	sumScore:			{filename: "sumscore",			permissions: ["write"]},
	sumUser:			{filename: "summary_user",		permissions: ["read"]},
	sumWeb:				{filename: "summary_web",		permissions: ["read"]},
	sOverview:			{filename: "study_overview",	permissions: false},
	trigger:			{filename: "trigger",			permissions: ["write"]},
	home:				{filename: "home",				permissions: false},
	userList:			{filename: "user_list",			permissions: []},
	userView:			{filename: "user_view",			permissions: []}
};