<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class DataFolderExists extends NoPermission {
	
	function exec(): array {
		if(Configs::getDataStore()->isInit())
			throw new PageFlowException('Disabled');
		else if(!isset($_POST['data_location']))
			throw new PageFlowException('Missing data');
		
		return Configs::getDataStore()->getESMiraInitializer()->getInfoArray($_POST['data_location']);
	}
}