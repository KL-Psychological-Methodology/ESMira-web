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
		$langCode = Main::getLang();
		
		$serverStore = Configs::getDataStore()->getServerStore();
		$output = '';
		
		$impressum = $serverStore->getImpressum($langCode);
		if(empty($impressum))
			$impressum = $serverStore->getImpressum('_');
		if(!empty($impressum)) {
			$output .= '<div class="title-row">'.Lang::get('impressum').'</div>' .$impressum;
		}
		
		$privacyPolicy = $serverStore->getPrivacyPolicy($langCode);
		if(empty($privacyPolicy))
			$privacyPolicy = $serverStore->getPrivacyPolicy('_');
		if(!empty($privacyPolicy)) {
			$output .= '<br/><br/><div class="title-row">'.Lang::get('privacyPolicy').'</div>' .$privacyPolicy;
		}
		
		return $output;
	}
}