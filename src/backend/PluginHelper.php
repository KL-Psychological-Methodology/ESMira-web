<?php

namespace backend;

use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use Throwable;


class PluginHelper {
	/**
	 * @var string
	 */
	private $pluginName;
	
	/**
	 * @var callable
	 */
	private $createData;
	
	/**
	 * Defines if plugin errors in overriding API scripts should be thrown and cancel the request or handled gracefully.
	 * Plugin errors will be logged either way.
	 * @var bool - throws errors if false. Otherwise, handles errors gracefully.
	 */
	public $handleApiOverrideErrorsGracefully = true;
	
	function __construct(string $pluginName, callable $createData) {
		$this->pluginName = $pluginName;
		$this->createData = $createData;
	}
	
	public function reportError(Throwable $e, string $msg = ''): void {
		Main::reportError($e, "Plugin $this->pluginName threw an exception!\n$msg");
	}
	public function report(string $message): void {
		Main::report("Report by $this->pluginName:\n$message");
	}
	
	/**
	 * Access the api data.
	 * @return mixed | null - the api data when called from `apiOverrides` or null when called from `pluginApi`.
	 */
	public function getApiData() {
		return ($this->createData)($this->pluginName);
	}
	
	
	/**
	 * Check if a file exists in the plugin directory.
	 * @param $folderName - the name / path of the folder that should be created. No leading slash is required.
	 * @throws CriticalException
	 */
	public function createFolder(string $folderName): void {
		$pluginPath = PathsFS::folderPluginData($this->pluginName);
		FileSystemBasics::createFolder("$pluginPath/$folderName");
	}
	
	/**
	 * Check if a file exists in the plugin directory.
	 * @param $fileName - the file name / path that should be checked. No leading slash is required.
	 * @return bool - true if the file exists, false if it does not.
	 */
	public function fileExists(string $fileName): bool {
		$pluginPath = PathsFS::folderPluginData($this->pluginName);
		return file_exists("$pluginPath/$fileName");
	}
	
	/**
	 * Renames a file or directory in the plugin directory.
	 * @param $from - the file name / path that should be renamed. No leading slash is required.
	 * @param $to - the new file name / path that should be used. No leading slash is required.
	 */
	public function rename(string $from, $to): void {
		$pluginPath = PathsFS::folderPluginData($this->pluginName);
		if(file_exists("$pluginPath/$from")) {
			rename("$pluginPath/$from", "$pluginPath/$to");
		}
	}
	
	/**
	 * Save a file in to the plugin directory.
	 * @param $fileName - the filename `$content` should be saved to. Can be a path ending with a filename. No leading slash is required.
	 * @param string $content - the content that should be saved under the provided `$subPath`.
	 * @throws CriticalException
	 */
	public function writeFile(string $fileName, string $content): void {
		$pluginPath = PathsFS::folderPluginData($this->pluginName);
		FileSystemBasics::createFolder($pluginPath);
		FileSystemBasics::writeFile("$pluginPath/$fileName", $content);
	}
	
	/**
	 * Delete a file from the plugin directory.
	 * @param $fileName - the name of the file that should be deleted. No leading slash is required.
	 */
	public function deleteFile(string $fileName): void {
		$pluginPath = PathsFS::folderPluginData($this->pluginName);
		unlink("$pluginPath/$fileName");
	}
	
	/**
	 * Load a file from the plugin directory.
	 * @param $fileName - the name of the file that should be loaded. No leading slash is required.
	 * @return string - the content of the file or false if it does not exist.
	 */
	public function loadFile(string $fileName): string {
		$pluginPath = PathsFS::folderPluginData($this->pluginName);
		return file_get_contents("$pluginPath/$fileName");
	}
	
	/**
	 * Returns JSON response that conveys a successful request.
	 * @param $content - the content that should be sent to the frontend.
	 * @param bool $isJsonString - if true, the string content will not be wrapped in quotes (Useful when returning a JSON string).
	 */
	public function respondSuccess(/*mixed*/ $content, bool $isJsonString = false) {
		if($isJsonString) {
			exit(JsonOutput::successString($content));
		}
		if(gettype($content) == 'string') {
			exit(JsonOutput::successString('"' .$content .'"'));
		}
		else {
			exit(JsonOutput::successObj($content));
		}
	}
	
	/**
	 * Checks if the current user has the given permission.
	 * @param $type - the permission type that should be checked. Can be "loggedIn", "admin", "create" or "canIssueFallbackTokens"
	 * @return bool
	 * @throws CriticalException
	 */
	public function checkPermission(string $type): bool {
		Main::sessionStart();
		switch($type) {
			case 'loggedIn':
				return Permission::isLoggedIn();
			case 'admin':
				return Permission::isAdmin();
			case 'create':
				return Permission::canCreate();
			case 'canIssueFallbackTokens':
				return Permission::canIssueFallbackTokens();
			default:
				return false;
		}
	}
	
	/**
	 * Checks if the current user has the given permission.
	 * @param $studyId - the study id the permission should be checked for.
	 * @param $type - the permission type that should be checked. Can be "read", "write" or "message"
	 * @return bool
	 * @throws CriticalException
	 */
	public function checkStudyPermission(int $studyId, string $type): bool {
		Main::sessionStart();
		return Permission::hasPermission($studyId, $type);
	}
	
	/**
	 * Returns JSON response that conveys an error.
	 * @param string $error - the error message that should be sent to the frontend.
	 */
	public function respondError(string $error) {
		exit(JsonOutput::error($error));
	}
}