<?php

return [
	'backup_interval_days' => 30, //in days; interval for backup warning
	'number_of_saved_days_in_server_statistics' => 14,
	'max_msgs_per_user' => 100,
	'max_possible_backups_per_day' => 1000, //should never be reached. Mainly there to prevent too many backups in case of a bug
	'max_filesize_for_changes' => 1000000000, //1gb; when variables are changed, lines will be adjusted. If file is too big, a new file will be created instead (after backup of course!)
	'dataset_server_timeout' => 1000, //milliseconds between received datasets (per user) or the server will decline them (excludes quit event)
	'statistics_timed_storage_max_entries' => 2000, //higher numbers leads to bigger files for statistics and higher performance impact when adding new values too statistics (and can also lag the statistic charts)
	'statistics_max_entries_at_once' => 1500,
	'smallest_timed_distance' => 675, //11min in seconds, needs to multiply into ONE_DAY (86400). Will be doubled when there are more entries than statistics_timed_storage_max_entries. All values inside this timeframe will be merged. A higher timeframe decreases the number of data that has to be saved into statistics
	
	'url_about_esmira_host' => 'esmira.kl.ac.at',
	'url_about_esmira_json_location' => '/documents/about/langs/%s.json',
	
	//CSV-Options:
	'csv_delimiter' => ';',
	
	'serverName' => ['_' => ''],
	'langCodes' => ['en']
];