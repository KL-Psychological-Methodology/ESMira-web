<?php

namespace backend;
require_once DIR_BASE .'/backend/responseFileKeys.php';

class ResponsesIndex {
	public $keys;
	public $types = [];
	public $backwardsAliases = []; // This associative array holds name aliases to preserve backwards compatibility, in cases where variable name changes are necessary
	
	public function __construct(array $keys = [], array $types = [], array $backwardsAliases = []) {
		$this->keys = $keys;
		$this->types = $types;
		$this->backwardsAliases = $backwardsAliases;
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
				for($i = 1; $i <= count($input->listChoices ?? []); $i++) {
					$this->addName("$name~$i");
					$itemName = $input->listChoices[$i-1];
					$this->backwardsAliases["$name~$i"] = "$name~$itemName";
				}
				if($input->other) {
					$this->addName("$name~other");
					$this->addName("$name~other_text");
				}
				break;
			case 'list_single':
				$this->addName($name);
				if($input->other) {
					$this->addName("$name~other");
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