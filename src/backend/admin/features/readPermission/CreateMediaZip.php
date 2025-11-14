<?php

namespace backend\admin\features\readPermission;

use backend\admin\HasReadPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Paths;

class CreateMediaZip extends HasReadPermission {
	/**
	 * Passes $content to echo and flushes the output immediately.
	 * Needs to be protected so it can be stubbed by tests.
	 * @param string $content the content to be flushed
	 */
	protected function flushProgress(string $content): void {
		echo $content;
		
		ob_flush();
		flush();
	}
	
	/**
	 * Sends necessary headers.
	 * Needed for testing.
	 */
	protected function sendHeader(): void {
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
	}
	
	function execAndOutput() {
		$this->sendHeader();
		$this->flushProgress("Start\n\n");

		$pathZip = Paths::fileMediaZip($this->studyId);
		if(!file_exists($pathZip)) //zip was not created or deleted, so we create it:
			Configs::getDataStore()->getResponsesStore()->createMediaZip(
				$this->studyId,
				function(string $content) {$this->flushProgress($content);}
			);
		
		$this->flushProgress("event: finished\ndata: \n\n");
	}
	
	function exec(): array {
		throw new CriticalException('Internal error. CreateMediaZip can only be used with execAndOutput()');
	}
}