> [!CAUTION]
> This update introduces scripting. To find out more on this new feature, head on over to the wiki page for [Scripting](https://github.com/KL-Psychological-Methodology/ESMira/wiki/Scripting-in-ESMira).

### 🚀 Added

- Scripting Features:
    - A code editor for ESMira's new scripting language, Merlin. This editor includes a linter, allowing you to detect syntax errors during study creation.
    - _Relevance_ scripts for both items and pages. These scripts allow to dynamically show or hide the respective item or page depending on the conditions specified in the script.
    - _Text_ scripts for items. These scripts allow for dynamically generating item text in code.
    - _Virtual Items_. These are similar to the sum scores already part of ESMira (and can be found in the same location). However, they only define a name that is expected and will be part of the data set. These value of these items can be programmatically set within scripts.
    - _Scripting End Block_. A script that gets executed right before a questionnaire is saved. This script can be used to, e.g., calculate statistics derived from items in the questionnaire and save these into virtual items.
- Option for vertical Likert scale. Some devices with smaller screens have trouble displaying Likert scales with a larger number of steps. This option allows to have a Likert scale rendered vertically, making it more compatible with all screen sizes.