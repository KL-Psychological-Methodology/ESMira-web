### ⚠️ Breaking
- Changed variable naming for multiple choice option columns (from "variable~itemtext" to "variable~itemnumber"). This should increase compatibility between multiple languages.
  - The server will try to automatically convert any outdated dataset containing data from such items sent by clients of previous verisons.
- Existing study configurations should automatically be updated. This includes:
  - Naming of variable columns
  - Calculations of sum scores using response option columns of multiple choice items.
  - Charts including response option columns of multiple choice items as variables. (**Note that this might not work properly in all cases. Please check any charts that might possibly be affected**).
- Existing datasets will automatically be updated to the new variable naming.
  - The update will try to automatically back up all study data before changing it. However, you might want to do this manually in case this does not work properly.