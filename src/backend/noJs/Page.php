<?php

namespace backend\noJs;

use backend\CriticalError;
use backend\PageFlowException;

interface Page {
	public function getTitle(): string;
	
	/**
	 * @throws ForwardingException
	 * @throws CriticalError
	 * @throws PageFlowException
	 */
	public function getContent(): string;
}