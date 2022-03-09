<?php
namespace backend\admin;

use backend\Base;
use backend\Files;
use backend\Output;
use backend\Permission;

abstract class HasReadPermission extends IsLoggedIn {
	
	function __construct() {
		parent::__construct();
		if($this->study_id == 0)
			Output::error('Missing study id');
		if(!$this->is_admin && !Permission::has_permission($this->study_id, 'read'))
			Output::error('No permission');
	}
}

?>