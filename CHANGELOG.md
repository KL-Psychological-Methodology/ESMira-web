# Changelog
## Version 2.0.7 (2022-10-07)
### ✏️ Changed
- Fix: Study list produced error in PHP 8


## Version 2.0.6 (2022-09-19)
### ✏️ Changed
- Improved update process to prevent breaking errors.
- Fix: Resolved errors when updating from 2.0.4 to 2.0.5.


## Version 2.0.5 (2022-09-16)
### ✏️ Changed
- Fix: Added missing images in english app instructions of version.
- Fix Changing language was not working.


## Version 2.0.4 (2022-09-12)
### ✏️ Changed
- Fix: Emptying a study generated faulty questionnaire files.


## Version 2.0.3 (2022-09-12)
### ✏️ Changed
- Fix: Faulty questionnaire files were generated in new studies 


## Version 2.0.2 (2022-09-12)
### ✏️ Changed
- Sort entries in internal server statistics.
- Fix: Error when creating new questionnaire.
- Fix: UI always claimed that there were new error reports.


## Version 2.0.1 (2022-09-09)
### ✏️ Changed
- Fix: Error when saving studies with questionnaires without an internalId.


## Version 2.0.0 (2022-09-08)
### ⚠️ Breaking
- ESMira data is reformatted when updating from a version before 2.0.0
  - Existing data should be reformatted automatically, but we advise to back up the data folder before updating to prevent existing studies from breaking in case we missed something.
  - Existing study responses will not be affected. But in case the response variables for a study are not reformatted correctly (which should not be possible), new study data might not be saved properly (missing variable data). Should that happen, freeze the study in the study settings (to make sure all data uploads are halted) and contact us asap (either via Issues or email).
  - Reformatting includes: 
    - Error logs,
    - Messages, 
    - Unprocessed study statistics,
    - Study statistics metadata cache, 
    - Response variables for saving data, 
    - Participant data cache
- All admin accounts (except for the one doing the update) will need to log in again.


### 🚀 Added
- Added tests for backend.

### ✏️ Changed
- Complete refactoring of backend code.
- Added abstraction layer to have the option of connecting a database in the future.
- Simplified code and data structure.
- Improved error handling in backend.
- Web version adds entry in web_access.csv when user navigates to study questionnaire.  
- Improved error handling in various places.
- Fix: Resolved issue when opening questionnaires via direct link (or qr code) in the web version.
- Fix: Various bugfixes in php fallback.
- Fix: Various other small bugfixes.

### 🗑️ Removed
- Removed missing backups warning on the admin page