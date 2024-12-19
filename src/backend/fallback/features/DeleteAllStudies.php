<?php

namespace backend\fallback\features;

use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\fallback\FallbackFeature;

class DeleteAllStudies extends FallbackFeature
{
	function exec(): array
	{
		$store = Configs::getDataStore()->getFallbackStudyStore($this->encodedUrl);
		$studyIdList = $store->getStudyIdList();
		foreach ($studyIdList as $studyId) {
			$store->delete($studyId);
		}

		return [];
	}
}