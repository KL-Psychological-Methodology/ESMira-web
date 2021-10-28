<?php

namespace backend\noJs;

use Exception;
use backend\noJs\Page;

class ForwardingException extends Exception {
	private $page;
	public function __construct(Page $page) {
		$this->page = $page;
		parent::__construct('Forwarding');
	}
	public function getPage() {
		return $this->page;
	}
}