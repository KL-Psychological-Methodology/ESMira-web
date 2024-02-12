### ⚠️ Breaking
- Changed variable naming for multiple choice option columns (from "variable~itemtext" to "variable~itemnumber"). This should increase compatibility between multiple languages.
  - The server will try to automatically convert any outdated dataset containing data from such items sent by clients of previous versions.
- Existing datasets will automatically be updated to the new variable naming.
  - The update will try to automatically back up all study datasets containing multiple choice items before changing them. (However, you might want to do this manually if you want to be on the safe side)

### 🚀 Added

- Added a "Study tag" field to studies, which will show up in the studies list in parentheses after the study name. This can be used to internally mark studies, e.g., to distinguish studies of the same name.
  - Note: While the ESMira app does not use/show this tag to participants, it is still part of the study configuration json. We do not recommend to put strictly confidential information in this tag.

### ✏️ Changed

- Added a confirmation dialog when deleting access keys of published studies.
- Added a decimal option to compass item (default is now to only save the integer part).
- Small improvements to the web questionnaire:
  - Fixed broken contact mail links.
  - Questionnaire is now omitting the "* input required" message if a page does not contain required inputs.
- Removed the "Error Reports" button in the admin interface for non-dev servers.
  - Currently, clients only send error reports to the dev-server "esmira.kl.ac.at", making this page superfluous for other instances.