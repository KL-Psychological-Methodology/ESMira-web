<?php
namespace backend\admin;

use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

abstract class IsLoggedIn extends NoPermission {
	protected $studyId;
	protected $isAdmin;
	
	function __construct() {
		parent::__construct();
		if(isset($_POST['accountName']) && isset($_POST['pass'])) {
			$accountName = $_POST['accountName'];
			$pass = $_POST['pass'];
			Permission::login($accountName, $pass);
		}
		if(!Permission::isLoggedIn() || !Configs::getDataStore()->isInit())
			throw new PageFlowException('No permission');
		
		$this->isAdmin = Permission::isAdmin();
		$this->studyId = (int) ($_POST['study_id'] ?? $_GET['study_id'] ?? 0);
	}
}

?>