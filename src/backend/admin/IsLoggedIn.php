<?php
namespace backend\admin;

use backend\Configs;
use backend\PageFlowException;
use backend\Permission;

abstract class IsLoggedIn extends NoPermission {
	protected $studyId;
	protected $isAdmin;
	
	function __construct() {
		parent::__construct();
		if(isset($_POST['user']) && isset($_POST['pass'])) {
			$user = $_POST['user'];
			$pass = $_POST['pass'];
			Permission::login($user, $pass);
		}
		if(!Permission::isLoggedIn() || !Configs::getDataStore()->isInit())
			throw new PageFlowException('No permission');
		
		$this->isAdmin = Permission::isAdmin();
		$this->studyId = (int) ($_POST['study_id'] ?? $_GET['study_id'] ?? 0);
	}
}

?>