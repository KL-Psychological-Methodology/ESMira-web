### ⚠️ Breaking
- The names for cookies in the **web version of the questionnaire** have changed. This leads to:
  - The state of dynamic items in the web version of the questionnaire will reset for participants after this update.
  - Participants will have to provide a username again in the web version of the questionnaire.
  - Participants will have to accept the informed consent again in the web version of the questionnaire.

### 🚀 Added
- Added new permission "create": Can create studies.
- Add new item: record_audio.
- Add new item: compass.
- Add new item: countdown.
- Add new item: share.
- Added new value "pageDuration": Data for questionnaires now also contain the duration for each page (studies that were created before this update need to be saved again to make sure this value is created in the data file).
- The item list_multiple now has one additional column per entry.
- Add new option to allow / disallow going back between questionnaire pages.

### ✏️ Changed
- Users with write permission can now also delete a study (previously, admin permission was needed).
- AppUsage item now also saves usageCountToday and usageTimeToday.
- Rewrite of the web version of the questionnaire.
- **Bugfix**: Questionnaire cookies (also in demo) would accumulate over time and make the page unresponsive.
- **Bugfix**: When creating a new study, the default unknown language was added twice.
- **Bugfix**: Server would sometime expect wrong item types when only study items were changed.
- **Bugfix**: Study updates would not respect user language.