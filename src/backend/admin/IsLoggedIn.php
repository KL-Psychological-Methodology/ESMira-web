<?php
namespace backend\admin;

use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

abstract class IsLoggedIn extends NoPermission {
	/**
	 * @var int
	 */
	protected $studyId;
	
	/**
	 * @var bool
	 */
	protected $isAdmin;
	
	function __construct() {
		parent::__construct();
		if(isset($_POST['accountName']) && isset($_POST['pass'])) {
			$accountName = $_POST['accountName'];
			$pass = $_POST['pass'];
			Permission::login($accountName, $pass);
		}
		if(!$this->isReady()) {
			throw new PageFlowException('Server is not ready');
		}
		if(!Permission::isLoggedIn()) {
			throw new PageFlowException('No permission');
		}
		
		$this->isAdmin = Permission::isAdmin();
		$this->studyId = (int) ($_POST['study_id'] ?? $_GET['study_id'] ?? 0);
	}
	
	/**
	 * Exists to be overridden by subclasses.
	 * Update and snapshot endpoints need to stay accessible even when being in maintenance mode
	 */
	protected function isReady(): bool {
		return Configs::getDataStore()->isReady();
	}
}
