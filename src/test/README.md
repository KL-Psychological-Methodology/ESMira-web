# How to set up the testing environment.

## Backend
> [!NOTE]
> In these instructions, replace `PATH_TO/ESMira-web` with the path to the project root.

### Install a PHP interpreter (with debugging) using Docker
A `docker-compose.yml` and a `Dockerfile` are provided in `PATH_TO/ESMira-web/src/test` to build php-cli with all necessary extensions:
- gd: Needed for testing image uploads.
- xdebug: Needed for debugging.
- zib: Needed for ESMira updates and plugin installation.

Run to build the PHP image:
```bash
cd PATH_TO/ESMira-web/src/test
docker-compose up -d --build esmira-test
```

### Jetbrains PHPStorm / Ultimate: Configure PHPUnit and XDebug
- Download [PHPUnit 9](https://phar.phpunit.de/phpunit-9.phar) (for PHP version 7.4) to `PATH_TO/ESMira-web/esmira/phpunit-9.phar`.
- Make sure your IDE has the following plugins installed: PHP, PHP Docker, PHP Remote Interpreter, Docker.
  - You will most likely have to install PHP Docker and PHP Remote Interpreter. They are needed to add external PHP Cli interpreters using Docker.
- Open `File -> Settings` and navigate to `Languages & Frameworks -> PHP`.
- Click the `...` button at `CLI Interpreter`.
- Click `+` at the top left to add a new Interpreter and select `From Docker, Vagrant, VM, WSL, Remote...`.
- In the new dialogue, select `Docker Compose` and the following values and then confirm the dialogue:
	- **Server**: Docker (should be filled out automatically).
	- **Configuration files**: Select `PATH_TO/ESMira-web/src/test/docker-compose.yml`.
	- **Service**: `esmira-test`.
- You can test your configuration by pressing the refresh button in `General` next to the `PHP executable` textbox. It should show PHP version 7.4.33 and Xdebug as a Debugger.
- Confirm the CLI Interpreters dialogue.
- Back in `Languages & Frameworks -> PHP` select the tab `PHP Runtime` and enable the following options (pressing `Sync Extensions with Interpreter` might do the same automatically):
	- `Bundled -> gd`
	- `External -> zib`
	- `Others -> xdebug`
- Go to `Languages & Frameworks -> PHP -> Test Frameworks` and click `+` to add a new Test Framework, select `PHPUnit by Remote Interpreter` and then select `esmira-test`.
- Under `PHPUnit library` select `Path to phpunit.phar`.
- In the textbox for `Path to phpunit.phar` input `/opt/project/esmira/phpunit-9.phar` (because this setting is relayed to the Docker container, the path needs to adhere to the container file structure and NOT to your local file structure).
- You should be ready to go and can confirm the settings.