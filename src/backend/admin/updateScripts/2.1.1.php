<?php

use backend\Configs;
use backend\FileSystemBasics;

$serverName = Configs::get('serverName');
$langCodes = Configs::get('langCodes');
if(!isset($serverName['en'])) {
	$serverName['en'] = $serverName['_'];
	unset($serverName['_']);
	$serverStore = Configs::getDataStore()->getServerStore();
	$impressum = $serverStore->getImpressum('_');
	$privacyPolicy = $serverStore->getPrivacyPolicy('_');
	
	$serverStore->saveImpressum($impressum, 'en');
	$serverStore->deleteImpressum($impressum, '_');
	$serverStore->saveImpressum($privacyPolicy, 'en');
	$serverStore->deleteImpressum($privacyPolicy, '_');
	
	$langCodes[] = 'en';
	FileSystemBasics::writeServerConfigs([
		'serverName' => $serverName,
		'langCodes' => $langCodes
	]);
}
else {
	$langCodes[] = '_';
	FileSystemBasics::writeServerConfigs([
		'langCodes' => $langCodes
	]);
}
