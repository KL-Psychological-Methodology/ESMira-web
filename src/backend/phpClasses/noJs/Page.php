<?php

namespace phpClasses\noJs;

use Exception;
use phpClasses\noJs\ForwardingException;

interface Page {
	public function getTitle();
	
	/**
	 * @throws Exception
	 * @throws ForwardingException
	 */
	public function getContent();
}