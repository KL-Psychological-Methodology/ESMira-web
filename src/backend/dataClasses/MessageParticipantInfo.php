<?php

namespace backend\dataClasses;

class MessageParticipantInfo {
	public $name;
	public $lastMsg;
	public $archived = false;
	public $pending = false;
	public $unread = false;
	
	public function __construct(string $name, int $lastMsg) {
		$this->name = $name;
		$this->lastMsg = $lastMsg;
	}
}