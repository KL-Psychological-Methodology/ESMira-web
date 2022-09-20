<?php
declare(strict_types=1);

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Configs;
use backend\FileSystemBasics;
use backend\exceptions\PageFlowException;
use backend\Permission;

class InitESMira extends NoPermission {
	function exec(): array {
		if(Configs::getDataStore()->isInit())
			throw new PageFlowException('Disabled');
		else if(!isset($_POST['new_account']) || !isset($_POST['pass']))
			throw new PageFlowException('Missing data');
		
		$user = $_POST['new_account'];
		$pass = $_POST['pass'];
		$initializer = Configs::getDataStore()->getESMiraInitializer();
		
		FileSystemBasics::writeServerConfigs($initializer->getConfigAdditions());
		$initializer->create($user, $pass);
		
		//
		//login:
		//
		Permission::setLoggedIn($user);
		$c = new GetPermissions();
		return $c->exec();
	}
}