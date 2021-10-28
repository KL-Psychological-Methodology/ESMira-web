<?php

namespace phpClasses\noJs\pages;

use Exception;
use phpClasses\noJs\Extra;
use phpClasses\noJs\ForwardingException;
use phpClasses\noJs\Lang;
use phpClasses\noJs\Page;

class InformedConsent implements Page {
	public function getTitle() {
		return Lang::get('informed_consent');
	}
	
	public function getContent() {
		$studyData = Extra::get_studyData();
		if(isset($studyData['notFound'])) {
			if(isset($studyData['error']))
				throw new Exception($studyData['error']);
			throw new ForwardingException(new StudiesList());
		}
		$study = $studyData['study'];
		
		$output = '<p>';
		if($study != null && isset($study->informedConsentForm))
			$output .= $study->informedConsentForm;
		$output .= '</p>
			<form method="post" action="" class="center">
				<input type="submit" name="informed_consent" value="' .Lang::get('i_agree') .'"/>
			</form>';
		
		return $output;
	}
}