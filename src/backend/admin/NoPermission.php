<?php
namespace backend\admin;

use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\exceptions\PageFlowException;

abstract class NoPermission {
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	function __construct() {}
	
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	function execAndOutput() {
		echo JsonOutput::successObj($this->exec());
	}
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	abstract function exec(): array;
}

?>