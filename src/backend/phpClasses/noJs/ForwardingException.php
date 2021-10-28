<?php

namespace phpClasses\noJs;

use Exception;
use phpClasses\noJs\Page;

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