<?php

namespace backend\fileSystem\subStores;

use backend\CreateDataSet;
use backend\exceptions\CriticalException;
use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;
use backend\FileUploader;
use backend\Paths;
use backend\PluginHelper;
use backend\subStores\PluginStore;
use stdClass;
use Throwable;
use ZipArchive;

class PluginStoreFS implements PluginStore {
	/**
	 * Iterates over all plugins in the plugin directory and applies a callback function to each plugin.
	 *
	 * @param callable $callback A callable function that will be invoked for each plugin. The plugin name will be passed as an argument to the callback.
	 */
	private function forEachPlugin(callable $callback): void {
		if(!$this->isEnabled()) {
			return;
		}
		
		$path = PathsFS::folderPluginRoot();
		$handle = opendir($path);
		while($file = readdir($handle)) {
			if($file[0] == '.') {
				continue;
			}
			$callback(Paths::getFromUrlFriendly($file));
		}
		closedir($handle);
	}
	
	/**
	 * Handles the execution of a specific API endpoint for all registered plugins.
	 *
	 * @param string $apiName The name of the API endpoint to be handled.
	 * @param callable $createData A callable function that generates the required data for the api endpoint. This data will be passed to the plugin's helper.
	 */
	private function handleApiEndpoint(string $apiName, callable $createData): void {
		function runPlugin(string $apiName, string $scriptPath, PluginHelper $helper) {
			try {
				if(file_exists($scriptPath)) {
					require $scriptPath;
				}
			}
			catch(Throwable $e) {
				$helper->reportError($e, "Error in $apiName");
				if(!$helper->handleApiOverrideErrorsGracefully) {
					throw $e;
				}
			}
		};
		
		$this->forEachPlugin(function($pluginId) use ($apiName, $createData) {
			$helper = new PluginHelper($pluginId, $createData);
			$apiPath = PathsFS::filePluginApiExtension($pluginId, $apiName);
			runPlugin($apiName, $apiPath, $helper);
		});
	}
	
	/**
	 * Retrieves an array of language codes available for a specified plugin.
	 *
	 * @param string $pluginId The id of the plugin for which to retrieve the language codes.
	 * @return array An array of language codes extracted from the plugin's language files.
	 */
	private function getPluginLangCodes(string $pluginId): array {
		$codes = [];
		
		$langPath = PathsFs::folderPluginLanguages($pluginId);
		$handle = opendir($langPath);
		while($file = readdir($handle)) {
			if($file[0] == '.') {
				continue;
			}
			$codes[] = explode('.', $file)[0];
		}
		closedir($handle);
		
		return $codes;
	}
	
	/**
	 * Prepares and saves language bundles for the specified language codes by combining
	 * language files from plugins and ensuring fallback to the English language where necessary.
	 *
	 * @param array $codes An array of language codes for which language bundles should be prepared.
	 * @throws CriticalException
	 */
	private function prepareLangBundles(array $codes): void {
		FileSystemBasics::createFolder(PathsFS::folderPluginCache());
		
		$enCollection = [];
		$this->forEachPlugin(function($pluginId) use (&$enCollection) {
			$langPath = PathsFS::filePluginLanguage($pluginId, 'en');
			if(file_exists($langPath)) {
				$enCollection[$pluginId] = json_decode(file_get_contents($langPath), true);
			}
		});
		
		foreach($codes as $code) {
			if($code == 'en') {
				$langCollection = $enCollection;
			}
			else {
				$langCollection = [];
				$this->forEachPlugin(function($pluginId) use (&$langCollection, $code, $enCollection) {
					$langPath = PathsFS::filePluginLanguage($pluginId, $code);
					if(file_exists($langPath)) {
						$json = json_decode(file_get_contents($langPath), true);
						if(isset($enCollection[$pluginId])) { //make sure untranslated INSIDE the plugin have a fallback
							$json = array_merge($enCollection[$pluginId], $json);
						}
						
						$langCollection[$pluginId] = $json;
					}
				});
			}
			
			
			//save / delete lang bundle
			$langBundlePath = PathsFS::filePluginLanguageBundle($code);
			if(count($langCollection) > 0) {
				$result = array_merge($enCollection, $langCollection); //make sure each plugin has an entry
				file_put_contents($langBundlePath, json_encode($result));
			}
			else if(file_exists($langBundlePath)) {
				unlink($langBundlePath);
			}
		}
	}
	
	/**
	 * Prepares and saves a JSON file containing frontend instruction data for all plugins.
	 * If no frontend instruction data is found, the JSON file will be removed.
	 *
	 * @throws CriticalException
	 */
	private function prepareFronendJson(): void {
		FileSystemBasics::createFolder(PathsFS::folderPluginCache());
		$output = [];
		$hasData = false;
		
		$this->forEachPlugin(function($pluginId) use (&$output, &$hasData) {
			$obj = ['sections' => []];
			$output[$pluginId] = &$obj;
			$metadataPath = PathsFS::filePluginStudyJsonInstructions($pluginId);
			if(file_exists($metadataPath)) {
				$obj['studyJsonDataStructure'] = json_decode(file_get_contents($metadataPath), true);
				$hasData = true;
			}
			
			$list = scandir(PathsFS::folderPluginFrontendSectionCodes($pluginId));
			foreach($list as $file) {
				if($file[0] == '.') {
					continue;
				}
				$obj['sections'][] = explode('.', $file)[0];
				$hasData = true;
			}
		});
		
		$path = PathsFS::filePluginFrontendJson();
		if($hasData) {
			file_put_contents($path, json_encode($output));
		}
		else {
			if(file_exists($path)) {
				unlink($path);
			}
		}
	}
	
	public function isEnabled(): bool {
		$path = PathsFS::folderPluginRoot();
		return file_exists($path) && !FileSystemBasics::isDirEmpty($path);
	}
	
	public function getPluginList(): array {
		$plugins = [];
		$this->forEachPlugin(function($pluginId) use (&$plugins) {
			$path = PathsFS::filePluginMetadata($pluginId);
			$metadata = json_decode(file_get_contents($path));
			
			if(!$metadata) {
				return;
			}
			$plugins[] = [
				'current' => $metadata,
				'newest' => $metadata->metadataUrl ? (json_decode(@file_get_contents($metadata->metadataUrl) ?? '{}') ?? []) : []
			];
		});
		return $plugins;
	}
	
	public function getFrontendPluginJson(): string {
		$path = PathsFS::filePluginFrontendJson();
		return file_exists($path) ? file_get_contents($path) : '{}';
	}
	
	
	public function getFrontendCode(string $pluginId, string $sectionName): string {
		$path = PathsFS::filePluginFrontendSectionCode($pluginId, $sectionName);
		return file_exists($path) ? file_get_contents($path) : '';
	}
	
	public function getLang(string $code): string {
		$langBundlePath = PathsFS::filePluginLanguageBundle($code);
		
		if(!file_exists($langBundlePath)) {
			$langBundlePath = PathsFS::filePluginLanguageBundle('en');
		}
		
		return file_exists($langBundlePath) ? file_get_contents($langBundlePath) : '{}';
	}
	
	public function isNotCompatible(stdClass $metadata): bool {
		$packageVersion = file_get_contents(Paths::FILE_SERVER_VERSION);
		return version_compare($packageVersion, $metadata->minESMiraVersion ?? $packageVersion)
			|| version_compare($metadata->maxESMiraVersion ?? $packageVersion, $packageVersion);
	}
	
	public function installPlugin(callable $createZipFile): void {
		$tempPluginPath = PathsFS::folderPluginTemp();
		$zipPath = "$tempPluginPath/.temp.zip";
		
		try {
			// Create plugin zip:
			
			if(file_exists($tempPluginPath)) {
				FileSystemBasics::emptyFolder($tempPluginPath);
			}
			else {
				FileSystemBasics::createFolder($tempPluginPath, true);
			}
			$createZipFile($zipPath);
			
			
			// Unzip:
			
			$zip = new ZipArchive();
			if(!$zip->open($zipPath) || !$zip->extractTo($tempPluginPath)) {
				$zip->close();
				throw new CriticalException("error_could_not_extract");
			}
			$zip->close();
			unlink($zipPath);
			
			
			// Check metadata:
			
			$tempMetadataPath = $tempPluginPath . '/' . PathsFS::FILENAME_PLUGIN_METADATA;
			if(!file_exists($tempMetadataPath)) {
				throw new CriticalException("error_not_a_valid_metadata_json");
			}
			$tempMetadata = json_decode(file_get_contents($tempMetadataPath));
			
			if(!$tempMetadata || !isset($tempMetadata->pluginId)) {
				throw new CriticalException("error_not_a_valid_metadata_json");
			}
			
			
			// Copy existing data:
			
			$pluginId = $tempMetadata->pluginId;
			if($pluginId != Paths::makeSafe($pluginId)) {
				throw new CriticalException("error_plugin_id_contains_invalid_characters");
			}
			
			$targetPluginPath = PathsFS::folderPlugin($pluginId);
			if(file_exists($targetPluginPath)) {
				$existingDataPath = PathsFS::folderPluginData($pluginId);
				if(file_exists($existingDataPath)) {
					rename($existingDataPath, $tempPluginPath . '/' . PathsFS::FOLDER_PLUGIN_DATA);
				}
				FileSystemBasics::emptyFolder($targetPluginPath);
				rmdir($targetPluginPath);
			}
			
			
			// Move to plugins folder:
			
			rename($tempPluginPath, $targetPluginPath);
			
			
			// Generate cache:
			
			$codes = $this->getPluginLangCodes($pluginId);
			$this->prepareLangBundles($codes);
			
			$this->prepareFronendJson();
		}
		finally {
			// Cleanup:
			if(file_exists($tempPluginPath)) {
				FileSystemBasics::emptyFolder($tempPluginPath);
				rmdir($tempPluginPath);
			}
		}
	}
	
	public function deletePlugin(string $pluginId): void {
		$codes = $this->getPluginLangCodes($pluginId);
		
		$path = PathsFS::folderPlugin($pluginId);
		if(file_exists($path)) {
			FileSystemBasics::emptyFolder($path);
			rmdir($path);
		}
		
		$this->prepareLangBundles($codes);
		$this->prepareFronendJson();
	}

	public function runPluginApi(string $pluginId, string $apiName): void {
		$path = PathsFS::filePluginApi($pluginId, $apiName);
		if(file_exists($path)) {
			$helper = new PluginHelper($pluginId, function() {return null;});
			require $path;
		}
		else {
			throw new CriticalException("Plugin $pluginId does not have an API endpoint $apiName");
		}
	}
	
	public function handleDataSetCache(array &$dataset, CreateDataSet $createDataSet): void {
		$data = [
			'dataset' => $dataset,
			'createDataSet' => $createDataSet,
		];
		$this->handleApiEndpoint('datasets', function() use($data) {return $data;});
	}
	
	public function handleFileUpload(int &$studyId, string &$userId, int &$identifier, FileUploader &$uploader): void {
		$data = [
			'studyId' => &$studyId,
			'userId' => &$userId,
			'identifier' => &$identifier,
			'uploader' => &$uploader,
		];
		$this->handleApiEndpoint('file_upload', function() use($data) {return $data;});
	}
	
	public function handleReceivingMessage(int &$studyId, string &$userId, string &$content): void {
		$data = [
			'studyId' => &$studyId,
			'userId' => &$userId,
			'content' => &$content,
		];
		$this->handleApiEndpoint('save_message', function() use($data) {return $data;});
	}
	
	public function handleMerlinLog(int &$studyId, string &$content): void {
		$data = [
			'studyId' => &$studyId,
			'content' => &$content,
		];
		$this->handleApiEndpoint('save_merlin_log', function() use($data) {return $data;});
	}
	
	public function handleErrorReport(string &$content): void {
		$this->handleApiEndpoint('save_errors', function() use(&$content) {return $content;});
	}
	
	public function handleAdminSaveStudy(stdClass &$studyCollection): void {
		$this->handleApiEndpoint('admin_SaveStudy', function($pluginId) use(&$studyCollection) {return [
			'studyCollection' => &$studyCollection,
			'pluginStudyData' => $studyCollection->_->pluginData->{$pluginId} ?? null
		];});
	}
}