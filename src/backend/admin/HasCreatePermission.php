<?php
namespace backend\admin;

use backend\exceptions\PageFlowException;
use backend\Permission;

abstract class HasCreatePermission extends IsLoggedIn {
	function __construct() {
		parent::__construct();
		if(!$this->isAdmin && !Permission::canCreate())
			throw new PageFlowException('No permission');
	}
}

?>