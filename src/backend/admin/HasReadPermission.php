<?php
namespace backend\admin;

use backend\exceptions\PageFlowException;
use backend\Permission;

abstract class HasReadPermission extends IsLoggedIn {
	
	function __construct() {
		parent::__construct();
		if($this->studyId == 0)
			throw new PageFlowException('Missing study id');
		if(!$this->isAdmin && !Permission::hasPermission($this->studyId, 'read'))
			throw new PageFlowException('No permission');
	}
}

?>