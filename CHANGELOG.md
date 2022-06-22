# Changelog
### ⚠️ Breaking
- Changed file format for: 
  - Error logs,
  - messages, 
  - unprocessed statistics,
  - statistics metadata, 
  - response variables, 
  - user data
- Existing data should be reformatted automatically, but we advise to back up the data folder before updating in case we missed something.
- Existing study responses will not be affected. In case the response variables for a study are not reformatted correctly (which should not be possible), new study data might not be saved properly. Should that happen, freeze the study in the study settings (so no further data can be lost) and contact us asap.

### 🚀 Added

- Added extensive tests for backend

### ✏️ Changed
- Complete refactoring of backend code
- Added abstraction layer to have the option of connecting a database in the future
- Simplified code and saving structure
- Bug fix: Resolved issue when opening questionnaires via direct link (or qr code) in the web version
