### ⚠️ Breaking

- Study Join Links: The new format of **QR-Codes** and **Links** including the fallback parameter can not be read by the current version of the ESMira client application. By the time this update comes out of alpha, the app will be updated to properly handle this optional parameter both in plain links and the QR codes encoding them. However, for the time being, please be cautious, as this could prevent participants from entering studies. If in doubt, disable the fallback system for sensitive studies for now.

### 🚀 Added

- Fallback System: This system allows to (unidirectionally) connect ESMira servers, so they may act as a fallback when participants try to enter a study. Study configuration files will automatically be saved to the fallback server. The QR-Codes participants can use to enter a study will then include an additional parameter encoding the fallback server's address. If the intended server is not reachable when a participant tries to sign up for a study the ESMira client app will then try to retrieve the study from the fallback server. All subsequent communication (e.g., data upload) is handled by the original server once it is reachable again. This feature includes several aspects:
    - A new user permission to allow the creation of setup tokens for connecting servers.
    - A system for connecting servers and testing the connection.
    - Studies can optionally disable the fallback system (opt-out).
    - QR-Codes and Join Links now contain a fallback parameter.
- Added the option to omit page numbering to questionnaires.
- Added the option to omit the toast message informing participants when pages have been skipped to questionnaires.
