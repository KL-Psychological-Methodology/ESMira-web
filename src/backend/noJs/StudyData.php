<?php

namespace backend\noJs;

use stdClass;

class StudyData {
	/**
	 * @var string
	 */
	public $accessKey;
	/**
	 * @var stdClass
	 */
	public $study;
	/**
	 * @var stdClass|null
	 */
	public $questionnaire;
	
	public function __construct(string $accessKey, stdClass $study, stdClass $questionnaire = null) {
		$this->accessKey = $accessKey;
		$this->study = $study;
		$this->questionnaire = $questionnaire;
	}
}