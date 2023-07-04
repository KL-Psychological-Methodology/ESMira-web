<?php
namespace backend\admin;

use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

require_once DIR_BASE.'backend/responseFileKeys.php';

abstract class HasWritePermission extends IsLoggedIn {
	function __construct() {
		parent::__construct();
		if($this->studyId == 0)
			throw new PageFlowException('Missing study id');
		if(
			!$this->isAdmin
			&& !Permission::hasPermission($this->studyId, 'write')
			&& (!Permission::canCreate() || Configs::getDataStore()->getStudyStore()->studyExists($this->studyId))
		) {
			throw new PageFlowException('No permission');
		}
	}
}

?>