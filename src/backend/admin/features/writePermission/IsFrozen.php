<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Base;
use backend\Output;

class IsFrozen extends HasWritePermission {
	
	function exec() {
		Output::successObj(Base::study_is_locked($this->study_id));
	}
}