<?php

namespace backend\fallback\features;

use backend\Configs;
use backend\fallback\FallbackFeature;

class DeleteAllStudiesExcept extends FallbackFeature
{
	function exec(): array
	{
		$keepList = isset($_GET['studyList']) ? json_decode($_GET['studyList']) : [];
		$store = Configs::getDataStore()->getFallbackStudyStore($this->encodedUrl);
		$studyIdList = $store->getStudyIdList();
		$deleteList = array_diff($studyIdList, $keepList);
		foreach ($deleteList as $studyId) {
			$store->delete($studyId);
		}
		return [];
	}
}