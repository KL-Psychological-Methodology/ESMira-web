<?php

namespace backend\admin\features\writePermission;

use backend\admin\HasWritePermission;
use backend\Configs;
use backend\exceptions\FallbackRequestException;
use backend\exceptions\PageFlowException;
use backend\FallbackRequest;

class DeleteStudy extends HasWritePermission
{
	function exec(): array
	{
		if ($this->studyId == 0)
			throw new PageFlowException('Missing data');

		$saver = Configs::getDataStore()->getStudyStore();
		$saver->delete($this->studyId);

		$this->handleFallback("DeleteStudy");

		return [$this->studyId];
	}
}
