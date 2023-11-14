<?php

use backend\Main;
use backend\Configs;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}

$lang = Main::getLang(false);
$defaultLangCode = Configs::get('defaultLang');
$serverStore = Configs::getDataStore()->getServerStore();

$type = $_GET['type'] ?? '';
switch($type) {
	case 'legal':
		$obj = [
			'impressum' => $serverStore->getImpressum($lang) ?: $serverStore->getImpressum($defaultLangCode),
			'privacyPolicy' => $serverStore->getPrivacyPolicy($lang) ?: $serverStore->getPrivacyPolicy($defaultLangCode)
		];
		break;
	default:
		$obj = [
			'homeMessage' => $serverStore->getHomeMessage($lang) ?: $serverStore->getHomeMessage($defaultLangCode),
		];
		break;
}
echo JsonOutput::successObj($obj);