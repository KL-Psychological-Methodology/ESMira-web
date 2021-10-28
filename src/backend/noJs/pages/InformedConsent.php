<?php

namespace backend\noJs\pages;

use Exception;
use backend\noJs\Extra;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\Page;

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