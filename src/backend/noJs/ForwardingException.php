<?php

namespace backend\noJs;

use Exception;

class ForwardingException extends Exception {
	/**
	 * @var Page
	 */
	private $page;
	
	public function __construct(Page $page) {
		$this->page = $page;
		parent::__construct(get_class($this->page));
	}
	public function getPage(): Page {
		return $this->page;
	}
}