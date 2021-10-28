<?php

namespace phpClasses\noJs\pages;

use phpClasses\Files;
use phpClasses\noJs\Lang;
use phpClasses\noJs\Page;

class Legal implements Page {
	public function getTitle() {
		return Lang::get('impressum');
	}
	
	public function getContent() {
		$langCode = Lang::getCode();
		
		$file_default_impressum = Files::get_file_langImpressum('_');
		$file_lang_impressum = Files::get_file_langImpressum($langCode);
		$output = '';
		
		if(file_exists($file_lang_impressum)) {
			$output .= '<div class="title-row">'.Lang::get('impressum').'</div>'
				.file_get_contents($file_lang_impressum);
		}
		else if(file_exists($file_default_impressum)) {
			$output .= '<div class="title-row">'.Lang::get('impressum').'</div>'
				.file_get_contents($file_default_impressum);
		}
		
		
		$file_default_privacyPolicy = Files::get_file_langPrivacyPolicy('_');
		$file_lang_privacyPolicy = Files::get_file_langPrivacyPolicy($langCode);
		if(file_exists($file_lang_privacyPolicy)) {
			$output .= '<br/><br/><div class="title-row">'.Lang::get('privacyPolicy').'</div>'
				.file_get_contents($file_lang_privacyPolicy);
		}
		else if(file_exists($file_default_privacyPolicy)) {
			$output .= '<br/><br/><div class="title-row">'.Lang::get('privacyPolicy').'</div>'
				.file_get_contents($file_default_privacyPolicy);
		}
		
		return $output;
	}
}