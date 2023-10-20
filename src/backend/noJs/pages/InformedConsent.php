<?php

namespace backend\noJs\pages;

use backend\exceptions\CriticalException;
use Exception;
use backend\noJs\NoJsMain;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\Page;

class InformedConsent implements Page {
	public function getTitle(): string {
		return Lang::get('informed_consent');
	}
	
	public function getContent(): string {
		$studyData = NoJsMain::getStudyData();
		$study = $studyData->study;
		
		$output = '<p class="wrap">';
		if($study != null && isset($study->informedConsentForm))
			$output .= $study->informedConsentForm;
		$output .= '</p><div class="titleRow">'.Lang::get('cookie_consent') .'</div>
			<p class="wrap">' .Lang::get('cookie_consent_desc') .'</p>
			<form method="post" action="" class="center">
				<input type="submit" name="informed_consent" value="' .Lang::get('i_agree') .'"/>
			</form>';
		
		return $output;
	}
}