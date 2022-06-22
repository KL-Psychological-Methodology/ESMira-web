<?php

namespace backend\dataClasses;

use backend\Main;

class Message {
	/**
	 * @var string
	 */
	public $from;
	/**
	 * @var string
	 */
	public $content;
	/**
	 * @var int
	 */
	public $sent;
	/**
	 * @var bool
	 */
	public $pending;
	/**
	 * @var bool
	 */
	public $unread;
	/**
	 * @var bool
	 */
	public $archived;
	/**
	 * @var int
	 */
	public $delivered = 0;
	/**
	 * @var int
	 */
	public $read = 0;
	
	public function __construct(string $from, string $content, bool $pending = false, bool $unread = false, bool $archived = false) {
		$this->from = $from;
		$this->content = $content;
		$this->sent = Main::getMilliseconds();
		$this->pending = $pending;
		$this->unread = $unread;
		$this->archived = $archived;
	}
}