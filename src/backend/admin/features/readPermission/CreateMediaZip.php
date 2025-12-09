<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\Paths;
use backend\SSE;

class CreateMediaZip extends HasReadPermission {
	private SSE $sse;
	
	/**
	 * Constructor is only needed for testing.
	 * @param SSE|null $sse SSE object to use for sending progress updates.
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function __construct(?SSE $sse = null) {
		parent::__construct();
		$this->sse = $sse ?? new SSE();
	}
	
	function execAndOutput() {
		$this->sse->sendHeader();

		$pathZip = Paths::fileMediaZip($this->studyId);
		if(!file_exists($pathZip)) {//zip was not created or deleted, so we create it:
			Configs::getDataStore()->getResponsesStore()->createMediaZip(
				$this->studyId,
				function(int $step, int $total) {
					$this->sse->flushProgress(1, 1, $step, $total);
				}
			);
		}
		
		$this->sse->flushFinished();
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. CreateMediaZip can only be used with execAndOutput()');
	}
}