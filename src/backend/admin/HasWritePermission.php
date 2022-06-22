<?php
namespace backend\admin;

use backend\PageFlowException;
use backend\Permission;

require_once DIR_BASE.'backend/responseFileKeys.php';

abstract class HasWritePermission extends IsLoggedIn {
	function __construct() {
		parent::__construct();
		if($this->studyId == 0)
			throw new PageFlowException('Missing study id');
		if(!$this->isAdmin && !Permission::hasPermission($this->studyId, 'write'))
			throw new PageFlowException('No permission');
	}
}

?>