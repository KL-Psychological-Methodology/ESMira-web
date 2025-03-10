<?php

namespace backend\fallback;

use backend\FallbackRequestOutput;

abstract class BaseFallbackFeature
{
	function execAndOutput()
	{
		echo FallbackRequestOutput::successObj($this->exec());
	}

	abstract function exec(): array;
}