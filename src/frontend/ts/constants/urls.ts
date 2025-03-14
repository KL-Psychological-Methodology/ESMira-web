export const URL_ABOUT_ESMIRA_SOURCE = 'https://raw.githubusercontent.com/KL-Psychological-Methodology/ESMira/main/about/'
export const URL_ABOUT_ESMIRA_STRUCTURE_JSON = URL_ABOUT_ESMIRA_SOURCE + "structure.json"
export const URL_ABOUT_ESMIRA_PUBLICATIONS_JSON = URL_ABOUT_ESMIRA_SOURCE + "publications.json"
export const URL_ABOUT_ESMIRA_JSON = URL_ABOUT_ESMIRA_SOURCE + "langs/%s.json"
export const URL_RELEASES_LIST = "https://api.github.com/repos/KL-Psychological-Methodology/ESMira-web/releases?per_page=50"
export const URL_DEV_SERVER = "esmira.kl.ac.at"
export const URL_BLOG_RSS = "https://kl-psychological-methodology.github.io/ESMira/feed.xml"

export const FILE_CHECK_HTACCESS = 'api/checkHtaccess/check.php'
export const FILE_ADMIN = 'api/admin.php'
export const FILE_RESPONSES = FILE_ADMIN + "?type=GetData&study_id=%1&q_id=%2"
export const FILE_CREATE_MEDIA = FILE_ADMIN + "?type=CreateMediaZip&study_id=%1"
export const FILE_MEDIA = FILE_ADMIN + "?type=GetMediaZip&study_id=%1"
export const FILE_IMAGE = FILE_ADMIN + "?type=GetMedia&study_id=%1&userId=%2&entryId=%3&key=%4&media_type=image"
export const FILE_AUDIO = FILE_ADMIN + "?type=GetMedia&study_id=%1&userId=%2&entryId=%3&key=%4&media_type=audio"
export const FILE_SAVE_ACCESS = 'api/access.php'
export const FILE_SETTINGS = 'api/settings.php?lang=%1&type=%2'
export const FILE_APP_INSTALL_INSTRUCTIONS = 'api/app_install_instructions.php?id=%d1&access_key=%s1&lang=%s2'
export const FILE_FALLBACK_APP_INSTALL_INSTRUCTIONS = 'api/app_install_instructions.php?id=%d1&key=%s1&lang=%s2&fromUrl=%s3'
export const FILE_GET_QUESTIONNAIRE = 'api/questionnaire.php?id=%d1&qid=%d2&access_key=%s1&lang=%s2&%s3'
export const FILE_SERVER_STATISTICS = 'api/server_statistics.php'
export const FILE_STATISTICS = 'api/statistics.php?id=%d&access_key=%s'
export const FILE_STUDIES = 'api/studies.php'