<?php
namespace backend\admin;

use backend\Base;
use backend\Files;
use backend\Output;
use backend\Permission;

abstract class HasAdminPermission extends IsLoggedIn {
	
	function __construct() {
		parent::__construct();
		if(!$this->is_admin)
			Output::error('No permission');
	}
}

?>