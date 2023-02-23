### 🚀 Added
- Added additional lists to reward code page.

### ✏️ Changed
- The min value for VaScale in the web questionnaire is now 1 instead of 0.
- Removed automatic logout when admin page was loaded from an old state (when page was cached). Now just reloading the page suffices.
- WYSIWYG fields now use "<br>" instead of "<div></div>" for hard breaks.
- Removed the servername length requirement
- **Bugfix**: Do not treat columns in data viewer called "date" as Date.
- **Bugfix**: Fix possibility of overwriting participant metadata.
- **Bugfix**: Fix labels of pie chart being clipped when value is too high
- **Bugfix**: Change server settings to allow big files.
- **Bugfix**: Fix Crash when languages where changes via JSON.
- **Bugfix**: Fix faulty ordering of messages in some situations.
- **Bugfix**: Sender of server message is not set to "server" anymore after message was read by user.