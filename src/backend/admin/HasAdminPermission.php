<?php
namespace backend\admin;

use backend\exceptions\PageFlowException;

abstract class HasAdminPermission extends IsLoggedIn {
	function __construct() {
		parent::__construct();
		if(!$this->isAdmin)
			throw new PageFlowException('No permission');
	}
}

?>