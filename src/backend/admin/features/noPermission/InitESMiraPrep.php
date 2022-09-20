<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Configs;
use backend\exceptions\PageFlowException;

class InitESMiraPrep extends NoPermission {
	function exec(): array {
		if(Configs::getDataStore()->isInit())
			throw new PageFlowException('Disabled');
		
		return Configs::getDataStore()->getESMiraInitializer()->getInfoArray();
	}
}