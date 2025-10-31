<?php

namespace backend\subStores;

use backend\CreateDataSet;
use backend\DataSetCacheContainer;
use backend\exceptions\CriticalException;
use backend\FileUploader;
use stdClass;


interface PluginStore {
	/**
	 * Checks if any plugin exists on the server.
	 * @return bool True if at least one plugin exists, false otherwise.
	 */
	public function isEnabled(): bool;
	
	/**
	 * Get a list of all plugins that are installed on the server.
	 * @return array An array of name, version, minESMiraVersion, maxESMiraVersion, website and metadataUrl for each plugin.
	 */
	public function getPluginList(): array;
	
	/**
	 * Returns the JSON string of the `frontend.json` file of a frontend plugin.
	 * @return string The JSON string of the plugin if the file exists, an empty string otherwise.
	 */
	public function getFrontendPluginJson(): string;
	
	/**
	 * Retrieve the JavaScript code for a specific plugin and section.
	 *
	 * @param string $pluginId The id of the plugin.
	 * @param string $sectionName The name of the section for which the frontend code is required.
	 * @return string The frontend code corresponding to the specified plugin and section.
	 */
	public function getFrontendCode(string $pluginId, string $sectionName): string;
	
	/**
	 * Retrieve the language JSON corresponding to the given language code.
	 * @param string $code The language code for which the language JSON should be retrieved.
	 * @return string The language JSON corresponding to the given code.
	 */
	public function getLang(string $code): string;
	
	/**
	 * Check if a plugin is compatible with the current ESMira version.
	 * @param stdClass $metadata The contents of the metadata.json of the plugin.
	 * @return bool true if the plugin is compatible, false otherwise.
	 */
	public function isNotCompatible(stdClass $metadata): bool;
	
	/**
	 * Install a plugin from a zip file.
	 * @param callable $createZipFile A callable that moves the plugin zip file to a given path.
	 * @throws CriticalException
	 */
	public function installPlugin(callable $createZipFile): void;
	
	/**
	 * Delete a plugin from the server.
	 * @param string $pluginId The id of the plugin.
	 * @throws CriticalException
	 */
	public function deletePlugin(string $pluginId): void;
	
	/**
	 * Run a specific php file that resides in a plugin's `pluginApi`'s folder.
	 * @param string $pluginId The id of the plugin.
	 * @param string $apiName The name of the API to run.
	 * @throws CriticalException
	 */
	public function runPluginApi(string $pluginId, string $apiName): void;
	
	/**
	 * Runs the file `datasets.php` from a plugin's `apiOverrides`'s folder.
	 * This function is meant to be called when ESMira is saving datasets.
	 * @param DataSetCacheContainer[] $dataset The dataset to process and handle caching for.
	 * @param CreateDataSet $createDataSet The createDataSet object that is used to create the dataset.
	 */
	public function handleDataSetCache(array &$dataset, CreateDataSet $createDataSet): void;
	
	/**
	 * Runs the file `file_upload.php` from a plugin's `apiOverrides`'s folder.
	 * This function is meant to be called when ESMira is handling file uploads for datasets.
	 * @param int $studyId The targeting study id for the uploaded file
	 * @param string $userId The userId for the uploaded file
	 * @param int $identifier The identifier for the uploaded file
	 * @param FileUploader $uploader The file uploader object that is used to save the uploaded file.
	 */
	public function handleFileUpload(int &$studyId, string &$userId, int &$identifier, FileUploader &$uploader): void;
	
	/**
	 * Runs the file `save_message.php` from a plugin's `apiOverrides`'s folder.
	 * This function is meant to be called when ESMira is saving an incoming study message.
	 * @param int $studyId The study id for the message
	 * @param string $userId The receiver of the message
	 * @param string $content The content of the message
	 */
	public function handleReceivingMessage(int &$studyId, string &$userId, string &$content): void;
	
	/**
	 * Runs the file `save_merlin_log.php` from a plugin's `apiOverrides`'s folder.
	 * This function is meant to be called when ESMira is handling an incoming merlin log.
	 * @param int $studyId The targeting study id for the log
	 * @param string $content The content of the log
	 */
	public function handleMerlinLog(int &$studyId, string &$content): void;
	
	/**
	 * Runs the file `save_errors.php` from a plugin's `apiOverrides`'s folder.
	 * This function is meant to be called when ESMira is handling an incoming error report.
	 * @param string $content The content of the error report
	 */
	public function handleErrorReport(string &$content): void;
	
	/**
	 * Runs the file `admin_SaveStudy.php` from a plugin's `apiOverrides`'s folder.
	 * This function is meant to be called when ESMira is saving a study..
	 * @param stdClass $studyCollection an object containing the a study JSON for each language (the main language can be found under `$studyCollection->_`)
	 */
	public function handleAdminSaveStudy(stdClass &$studyCollection): void;
}