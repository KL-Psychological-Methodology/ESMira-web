<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;
use backend\exceptions\FallbackRequestException;
use backend\FallbackRequest;

class FreezeStudy extends HasWritePermission
{
	function exec(): array
	{
		$studyStore = Configs::getDataStore()->getStudyStore();
		$studyStore->lockStudy($this->studyId, isset($_GET['frozen']));

		$this->handleFallback("FreezeStudy", isset($_GET['frozen']) ? ['frozen' => true] : []);

		return [$studyStore->isLocked($this->studyId)];
	}
}
