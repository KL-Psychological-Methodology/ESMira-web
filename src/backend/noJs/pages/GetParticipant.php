<?php

namespace backend\noJs\pages;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use Exception;
use backend\noJs\NoJsMain;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\Page;
use stdClass;

class GetParticipant implements Page {
	public function getTitle(): string {
		return Lang::get('user_id');
	}
	
	public function getContent(): string {
		$studyData = NoJsMain::getStudyData();
		$study = $studyData->study;
		return '<p>'
			.($study->chooseUsernameInstructions
				?? Lang::get('default_chooseUsernameInstructions')
				?: Lang::get('default_chooseUsernameInstructions')
			)
			.'</p>
	
	<form method="post" action="" class="center">
		<p>
			<label>
				<small>' .Lang::get('user_id') .'</small>
				<input name="participant" type="text" value=""/>
			</label>
		</p>
		<input name="new_participant" type="hidden" value="1"/>
		<input type="hidden" name="accept_informedConsent" value="1"/>
		<input type="submit" value="' .Lang::get('save') .'"/>
	</form>';
	}
}