<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\FileUploader;
use backend\Paths;

class InstallPlugin extends HasAdminPermission {
    function exec(): array {
		//get fileData:
		
        $dataStore = Configs::getDataStore();
        $pluginsStore = $dataStore->getPluginStore();
		
		$pluginsStore->installPlugin(function($zipPath) use($pluginsStore) {
			if(isset($_POST['manifestUrl'])) {
				//Handle metadata.json:
				
				$manifestUrl = Paths::getFromUrlFriendly($_POST['manifestUrl']);
				if(!substr($manifestUrl, 0, 4) != 'http' || substr($manifestUrl, -13, 13) != 'metadata.json') {
					throw new CriticalException('error_not_a_valid_metadata_json');
				}
				$metadata = json_decode(file_get_contents($manifestUrl));
				
				if(!$metadata || !isset($metadata->downloadUrl)) {
					throw new CriticalException('error_not_a_valid_metadata_json');
				}
				
				if($pluginsStore->isNotCompatible($metadata)) {
					throw new CriticalException('error_plugin_not_compatible');
				}
				
				//Handle download zip:
				
				$res = fopen($metadata->downloadUrl, 'r');
				if(!$res || !file_put_contents($zipPath, $res)) {
					fclose($res);
					throw new CriticalException("Downloading from $metadata->downloadUrl failed.");
				}
				fclose($res);
			}
			else if(isset($_FILES['plugin'])) {
				if(!isset($_FILES["plugin"])) {
					throw new CriticalException("error_unknown_data");
				}
				$uploader = new FileUploader($_FILES["plugin"]);
				if(!$uploader->upload($zipPath)) {
					throw new CriticalException("error_unknown");
				}
			}
			else {
				throw new CriticalException("error_unknown_data");
			}
		});
		
		return [];
    }
}
