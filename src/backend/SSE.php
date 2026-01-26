<?php

namespace backend;

class SSE {
	/**
	 * @var int
	 */
	private $lastProgress = -1;
	
	function sendHeader() {
		set_time_limit(10 * 60);
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('X-Accell-Buffering: no');
	}
	
	/**
	 * Passes $content to echo and flushes the output immediately.
	 * @param string $content the content to be flushed
	 */
	function flushContent(string $content): void {
		echo $content;
		
		ob_flush();
		flush();
	}
	
	function flushProgress(int $stage, int $maxStages, int $step, int $total): void {
		$stagePercent = 100 / $maxStages;
		$percent = round($stagePercent * ($stage - 1) + (($step / $total) * $stagePercent));
		
		if($percent == $this->lastProgress) {
			return;
		}
		$this->flushContent("event: progress\ndata: {\"progress\": $percent}\n\n");
		$this->lastProgress = $percent;
	}
	
	function flushFinished(): void {
		$this->flushContent("event: finished\ndata: {}\n\n");
	}
	function flushFailed(string $msg): void {
		$msg = str_replace(array("\r", "\n"), '', $msg);
		$this->flushContent("event: failed\ndata: $msg\n\n");
	}
}