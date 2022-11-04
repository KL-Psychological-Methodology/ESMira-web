<?php

namespace backend\noJs;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;

interface Page {
	public function getTitle(): string;
	
	/**
	 * @throws ForwardingException
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function getContent(): string;
}