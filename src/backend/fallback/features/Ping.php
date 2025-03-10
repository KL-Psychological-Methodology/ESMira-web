<?php

namespace backend\fallback\features;

use backend\fallback\FallbackFeature;

class Ping extends FallbackFeature
{
	function exec(): array
	{
		return ["pong"];
	}
}
