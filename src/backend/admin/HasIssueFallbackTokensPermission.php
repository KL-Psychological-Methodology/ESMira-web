<?php

namespace backend\admin;

use backend\exceptions\PageFlowException;
use backend\Permission;

abstract class HasIssueFallbackTokensPermission extends IsLoggedIn
{
	function __construct()
	{
		parent::__construct();
		if (!$this->isAdmin && !Permission::canIssueFallbackTokens())
			throw new PageFlowException('No permission');
	}
}