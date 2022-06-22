<?php

namespace backend\dataClasses;

class MessagesList {
	/**
	 * @var [Message]
	 */
	public $archive;
	/**
	 * @var [Message]
	 */
	public $pending;
	/**
	 * @var [Message]
	 */
	public $unread;
	
	public function __construct(array $archive, array $pending, array $unread) {
		$this->archive = $archive;
		$this->pending = $pending;
		$this->unread = $unread;
	}
	
	public static function get(array $archive, array $pending, array $unread): array {
		return [
			'archive' => $archive,
			'pending' => $pending,
			'unread' => $unread
		];
	}
}