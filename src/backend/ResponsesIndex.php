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
	
	public function addInput(\stdClass $input) {
		$name = $input->name;
		switch($input->responseType ?? 'text_input') {
			case 'text':
				return;
			case 'app_usage':
				$this->addName($name);
				$this->addName("$name~usageCountYesterday");
				$this->addName("$name~usageCountToday");
				$this->addName("$name~usageTimeYesterday");
				$this->addName("$name~usageTimeToday");
				break;
			case 'bluetooth_devices':
				$this->addName($name);
				$this->addName("$name~devices");
				break;
			case 'dynamic_input':
				$this->addName($name);
				$this->addName("$name~index");
				break;
			case 'list_multiple':
				$this->addName($name);
				foreach($input->listChoices ?? [] as $entry) {
					$this->addName("$name~$entry");
				}
				break;
			case 'file_upload':
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