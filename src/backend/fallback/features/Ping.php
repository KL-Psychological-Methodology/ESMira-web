<?php

namespace backend\fallback\features;

use backend\admin\HasAdminPermission;

class Ping extends HasAdminPermission
{
	function exec(): array
	{
		return ["pong"];
	}
}