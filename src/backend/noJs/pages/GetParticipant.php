<?php

namespace backend\noJs\pages;

use Exception;
use backend\noJs\Extra;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\Page;

class GetParticipant implements Page {
	private $study;
	
	/**
	 * @throws ForwardingException
	 * @throws Exception
	 */
	public function __construct() {
		$studyData = Extra::get_studyData();
		if(isset($studyData['notFound'])) {
			if(isset($studyData['error']))
				throw new Exception($studyData['error']);
			throw new ForwardingException(new StudiesList());
		}
		$this->study = $studyData['study'];
	}
	
	public function getTitle() {
		return Lang::get('user_id');
	}
	
	/**
	 * @inheritDoc
	 */
	public function getContent() {
		return '<p>' .($this->study != null && isset($this->study->chooseUsernameInstructions)
				? $this->study->chooseUsernameInstructions
				: Lang::get('default_chooseUsernameInstructions')) .'</p>
	
	<form method="post" action="" class="center">
		<p>
			<label>
				<small>' .Lang::get('user_id') .'</small>
				<input name="participant" type="text" value=""/>
			</label>
			<input name="new_participant" type="hidden" value="1"/>
			<input type="hidden" name="accept_informedConsent" value="1"/>
			<input type="submit" value="' .Lang::get('save') .'"/>
		</p>
	</form>';
	}
}