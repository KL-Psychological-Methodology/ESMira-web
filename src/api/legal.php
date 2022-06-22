<?php

use backend\Main;
use backend\Configs;
use backend\JsonOutput;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

if(!Configs::getDataStore()->isInit()) {
	echo JsonOutput::error('ESMira is not initialized yet.');
	return;
}

$lang = Main::getLang();

$serverStore = Configs::getDataStore()->getServerStore();
echo JsonOutput::successObj([
	'impressum' => $serverStore->getImpressum($lang) ?: $serverStore->getImpressum('_'),
	'privacyPolicy' => $serverStore->getPrivacyPolicy($lang) ?: $serverStore->getPrivacyPolicy('_')
]);