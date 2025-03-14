<?php

namespace backend\fallback\features;

use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\fallback\FallbackFeature;

class DeleteStudy extends FallbackFeature
{
	function exec(): array
	{
		if (!isset($_POST['studyId']))
			throw new PageFlowException('Missing data');
		$studyId = $_POST['studyId'];
		$store = Configs::getDataStore()->getFallbackStudyStore($this->encodedUrl);
		$store->delete($studyId);

		return [$studyId];
	}
}
