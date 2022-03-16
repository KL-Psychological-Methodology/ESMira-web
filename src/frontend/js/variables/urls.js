export const URL_ABOUT_ESMIRA_SOURCE = 'https://esmira.kl.ac.at/documents/about/',
	URL_ABOUT_ESMIRA_JSON = URL_ABOUT_ESMIRA_SOURCE+"langs/%s.json",
	
	FOLDER_ERRORS = 'data/errors/',
	
	FILE_CHECK_HTACCESS = 'api/check_htaccess/check.php',
	FILE_ADMIN = 'api/admin.php',
	FILE_RESPONSES = FILE_ADMIN+"?type=get_data&study_id=%1&q_id=%2",
	FILE_MEDIA = FILE_ADMIN+"?type=create_mediaZip&study_id=%1",
	FILE_IMAGE = FILE_ADMIN+"?type=get_mediaImage&study_id=%1&userId=%2&uploaded=%3&responseTime=%4&key=%5",
	FILE_SAVE_ACCESS = 'api/access.php',
	FILE_LEGAL = 'api/legal.php',
	FILE_SAVE_DATASET = 'api/datasets.php',
	FILE_SERVER_STATISTICS = 'api/server_statistics.php',
	FILE_STATISTICS = 'api/statistics.php?id=%d&access_key=%s',
	FILE_STUDIES = 'api/studies.php';