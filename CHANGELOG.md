### 🚀 Added
- **Added "publications" view that be accessed from the public website.**
- **New Calendar view for previewing questionnaire runtimes and scheduled events.**
- **New questionnaire item drawer.**
- **New "Filter and trigger" view that combines "Availability and runtime" and "Edit triggers".**
- Added option to easily copy existing studies.
- Added option to easily copy existing questionnaires.
- Added option to easily move pages into other questionnaires.
- Added copy buttons to pages, items, charts.
- Added quick access buttons to message view, to quickly filter for participants data.
- Added drop down menus to navigation bar.
- Added used disk space overview in server statistics (for admins)
- Added the option to only show studies by specific users
  - Be aware that this feature relies on data (creator of a study) that was not saved prior to this update. As a workaround, all existing studies (at the time of updating ESMira) will have the user, which updated ESMira, saved as their creator. 

### ✏️ Changed
- **Complete rewrite of all frontend components and migration to TypeScript.**
- Reworked "Publication, Links & QR code" view.
- Admin interface now only selectively loads studies, which decreases loading time.
- Optimized caching and page reloading.
- Improvements for statistics.
- Study source code now incorporates all study language versions.
- Added support for inPercent option (in combination with frequent distribution) for charts in web view.
- Access keys that share the same study are now automatically combined in the study list. 
- Bugfix: Fixed error in login history.
- Bugfix: Added missing variable for bluetooth_devices.
- Bugfix: Prevent deleting the parent directory files when update fails and automatically reverts update.
- Bugfix: Automatically recreate server statistics instead of throwing an error when json file is corrupted.
- Bugfix: Check for disk space before attempting to save data.