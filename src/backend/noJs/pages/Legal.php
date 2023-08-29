<?php

namespace backend\noJs\pages;

use backend\Configs;
use backend\Main;
use backend\noJs\Lang;
use backend\noJs\Page;

class Legal implements Page {
	public function getTitle(): string {
		return Lang::get('impressum');
	}
	
	public function getContent(): string {
		$langCode = Main::getLang(false);
		
		$serverStore = Configs::getDataStore()->getServerStore();
		$output = '';
		$defaultLangCode = Configs::get('defaultLang');
		
		$impressum = $serverStore->getImpressum($langCode);
		if(empty($impressum))
			$impressum = $serverStore->getImpressum($defaultLangCode);
		if(!empty($impressum)) {
			$output .= '<div class="titleRow">'.Lang::get('impressum').'</div>' .$impressum;
		}
		
		$privacyPolicy = $serverStore->getPrivacyPolicy($langCode);
		if(empty($privacyPolicy))
			$privacyPolicy = $serverStore->getPrivacyPolicy($defaultLangCode);
		if(!empty($privacyPolicy)) {
			$output .= '<br/><br/><div class="titleRow">'.Lang::get('privacyPolicy').'</div>' .$privacyPolicy;
		}
		
		return $output;
	}
}