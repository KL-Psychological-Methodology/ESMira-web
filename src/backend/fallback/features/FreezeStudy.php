<?php

namespace backend\fallback\features;

use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\fallback\FallbackFeature;

class FreezeStudy extends FallbackFeature
{
	function exec(): array
	{
		if (!isset($_POST['studyId']))
			throw new PageFlowException("Missing data");
		$studyId = $_POST['studyId'];
		$studyStore = Configs::getDataStore()->getFallbackStudyStore($this->encodedUrl);
		$studyStore->lockStudy($studyId, isset($_POST['frozen']));
		return [$studyStore->isLocked($studyId)];
	}
}