<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Base;
use backend\Output;

class FreezeStudy extends HasWritePermission {
	
	function exec() {
		Base::freeze_study($this->study_id, isset($_GET['frozen']));
		Output::successObj(Base::study_is_locked($this->study_id));
	}
}