# Changelog
### ⚠️ Breaking
- Changed file format for: 
  - Error logs,
  - messages, 
  - unprocessed study statistics,
  - study statistics metadata cache, 
  - response variables for saving data, 
  - participant data cache
- Existing data should be reformatted automatically, but we advise to back up the data folder before updating in case we missed something.
- Existing study responses will not be affected. In case the response variables for a study are not reformatted correctly (which should not be possible), new study data might not be saved properly. Should that happen, freeze the study in the study settings (to make sure all data uploads are halted) and contact us asap.

### 🚀 Added

- Added extensive tests for backend

### ✏️ Changed
- Complete refactoring of backend code.
- Added abstraction layer to have the option of connecting a database in the future.
- Simplified code and data structure.
- Improved error handling in backend.
- Web version adds entry in web_access.csv when user navigates to study questionnaire.  
- Improved error handling in various places.
- Bugfix: Error when saving studies with questionnaires without an internalId.
- Bugfix: Resolved issue when opening questionnaires via direct link (or qr code) in the web version.
- Bugfix: Changing language was ignored when using a short link to a study.
- Various bugfixes in php fallback.
- Various other small bugfixes.

### 🗑️ Removed
- Removed missing backups warning on the admin page