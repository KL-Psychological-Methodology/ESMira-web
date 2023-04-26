<?php

namespace backend;
require_once DIR_BASE .'/backend/responseFileKeys.php';

class ResponsesIndex {
	public $keys;
	public $types = [];
	
	public function __construct(array $keys = [], array $types = []) {
		$this->keys = $keys;
		$this->types = $types;
	}
	
	public function addInput($responseType, $name) {
		switch($responseType) {
			case 'text':
				return;
			case 'dynamic_input':
				$this->addName($name);
				$this->addName("$name~index");
				break;
			case 'app_usage':
				$this->addName($name);
				$this->addName("$name~usageCount");
				break;
			case 'photo':
				$this->addName($name);
				$this->types[$name] = 'image';
				break;
			case 'record_audio':
				$this->addName($name);
				$this->types[$name] = 'audio';
				break;
			default:
				$this->addName($name);
				break;
		}
	}
	public function addName($name) {
		$this->keys[] = $name;
	}
}