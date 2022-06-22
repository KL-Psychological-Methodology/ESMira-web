<?php
namespace backend\admin;

use backend\CriticalError;
use backend\JsonOutput;
use backend\PageFlowException;

abstract class NoPermission {
	/**
	 * @throws PageFlowException
	 * @throws CriticalError
	 */
	function __construct() {}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalError
	 */
	function execAndOutput() {
		echo JsonOutput::successObj($this->exec());
	}
	
	/**
	 * @throws CriticalError
	 * @throws PageFlowException
	 */
	abstract function exec(): array;
}

?>