<?php

namespace backend\noJs;

use Exception;
use backend\noJs\ForwardingException;

interface Page {
	public function getTitle();
	
	/**
	 * @throws Exception
	 * @throws ForwardingException
	 */
	public function getContent();
}