<?php
namespace backend\admin;

use backend\exceptions\CriticalException;
use backend\JsonOutput;
use backend\exceptions\PageFlowException;

abstract class NoPermission {
	/**
	 * @throws \backend\exceptions\PageFlowException
	 * @throws CriticalException
	 */
	function __construct() {}
	
	/**
	 * @throws \backend\exceptions\PageFlowException
	 * @throws CriticalException
	 */
	function execAndOutput() {
		echo JsonOutput::successObj($this->exec());
	}
	
	/**
	 * @throws \backend\exceptions\CriticalException
	 * @throws \backend\exceptions\PageFlowException
	 */
	abstract function exec(): array;
}

?>