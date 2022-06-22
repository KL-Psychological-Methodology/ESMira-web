<?php

namespace backend;

class DataSetCacheStatisticsEntry {
	/**
	 * @var string
	 */
	public $key;
	/**
	 * @var int
	 */
	public $index;
	/**
	 * @var int
	 */
	public $timestamp;
	/**
	 * @var string
	 */
	public $answer;
	
	public function __construct(string $key, int $index, int $timestamp, string $answer) {
		$this->key = $key;
		$this->index = $index;
		$this->timestamp = $timestamp;
		$this->answer = $answer;
	}
}