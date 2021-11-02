export const
	ONE_DAY = 86400, //in seconds: 60*60*24
	CHART_MIN_ENTRY_WIDTH = 35,
	SMALLEST_TIMED_DISTANCE = 675, //11min in seconds, needs to multiply into ONE_DAY. Will be doubled when there are more entries than STATISTICS_TIMED_STORAGE_MAX_ENTRIES. All values inside this timeframe will be merged. A higher timeframe decreases the number of data that has to be saved into statistics
	
	ACTION_INVITATION = 1,
	ACTION_MESSAGE = 2,
	ACTION_NOTIFICATION = 3,
	
	DATA_MAIN_KEYS = [
		"userId",
		"uploaded",
		"appType",
		"appVersion",
		"studyId",
		"studyVersion",
		"questionnaireName",
		"eventType",
		"timezone",
		"responseTime",
		"responseTime_formatted"
	];