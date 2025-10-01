### 🚀 Added

- Button to delete backups of study data
- Polish language translation
- Warnings for
    - Invalid signal time configuration
    - Reuse of access keys across studies
- FAQ field in study description (will show as separate button in App)
- Clearer headings and links to explanations for different link types on publish page
- Compatibility of docker image with ARM64 (Thanks to Jakub Jędrusiak!)
- Checks and warnings for some fields (e.g., warning if email address does not follow email address pattern)
- Option to make items be skipped by the check for hinting at skipped questions

### ✏️ Changed

- Workaround for crash due to failing to load the bookmark loader
- Separated app participation instructions into two steps
- Copying an element (e.g., an item) now opens the new element in a new section
- Wherever possible number inputs were reworked to not allow "illegal" values (e.g., negative frequencies)
- Fixed a bug that prevented statistics from working with metadata
- Moved QR code view in publish page to the right
- Made charts in the web view properly respect the max y setting
- Ensured that missing strings use a default language fallback (server side)
- Resolved the "fixed random" setting being ignored on signal configurations
