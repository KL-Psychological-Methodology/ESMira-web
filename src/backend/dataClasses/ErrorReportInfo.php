<?php

namespace backend\dataClasses;

class ErrorReportInfo {
	/**
	 * @var int
	 */
	public $timestamp;
	/**
	 * @var string
	 */
	public $note;
	/**
	 * @var bool
	 */
	public $seen;
	
	public function __construct(int $timestamp, string $note = '', bool $seen = false) {
		$this->timestamp = $timestamp;
		$this->note = $note;
		$this->seen = $seen;
	}
}