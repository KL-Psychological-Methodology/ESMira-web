<?php

namespace backend;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../autoload.php';

/**
 * Test class for the SSE class.
 * This class focuses on testing the functionality of the `flushProgress` method.
 */
class SSETest extends TestCase {
	private SSE $sse;
	
	protected function setUp(): void {
		$this->sse = $this->createPartialMock(SSE::class, ['flushContent']);
	}
	
	/**
	 * Test that `flushProgress` flushes the correct progress only when progress changes.
	 */
	public function testFlushProgressWithNewProgress(): void {
		$invokedCount = $this->exactly(2);
		$this->sse
			->expects($invokedCount)
			->method('flushContent')
			->willReturnCallback(function($content) use($invokedCount) {
				if($invokedCount->getInvocationCount() === 1) {
					$this->assertSame("event: progress\ndata: {\"progress\": 25}\n\n", $content);
				}
				else if($invokedCount->getInvocationCount() === 2) {
					$this->assertSame("event: progress\ndata: {\"progress\": 50}\n\n", $content);
				}
				
			});
		
		$this->sse->flushProgress(1, 1, 1, 4);
		$this->sse->flushProgress(1, 1, 1, 4);
		$this->sse->flushProgress(1, 1, 2, 4);
	}
	
	/**
	 * Test that `flushProgress` calculates and flushes 100% progress correctly.
	 */
	public function testFlushProgressAtCompletion(): void {
		$this->sse
			->expects($this->once())
			->method('flushContent')
			->with("event: progress\ndata: {\"progress\": 100}\n\n");
		
		$this->sse->flushProgress(4, 4, 4, 4);
	}
	
	/**
	 * Test that `flushProgress` handles scenarios where $step is 0.
	 */
	public function testFlushProgressWithZeroStep(): void {
		$this->sse
			->expects($this->once())
			->method('flushContent')
			->with("event: progress\ndata: {\"progress\": 75}\n\n");
		
		$this->sse->flushProgress(4, 4, 0, 4);
	}
}