<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\FallbackRequestException;
use backend\exceptions\PageFlowException;
use backend\FallbackRequest;

class SynchAllStudiesToFallback extends HasAdminPermission
{
	function exec(): array
	{
		if (!isset($_POST['url']))
			throw new PageFlowException('Missing data');
		$url = base64_decode($_POST['url']);

		$studyStore = Configs::getDataStore()->getStudyStore();
		$studyIdList = $studyStore->getStudyIdList();

		$request = new FallbackRequest();
		try {
			$request->postRequest($url, "DeleteAllStudiesExcept", ['studyList' => json_encode($studyIdList)]);
		} catch (FallbackRequestException $e) {
			throw new PageFlowException($e->getMessage());
		}

		foreach ($studyIdList as $studyId) {
			$studyBundle = '{"_": ' . $studyStore->getStudyConfigAsJson($studyId) . ', "languages": ' . $studyStore->getAllLangConfigsAsJson($studyId) . '}';
			$request = new FallbackRequest();
			try {
				$request->postRequest($url, "SaveStudy", ['studyBundle' => $studyBundle]);
			} catch (FallbackRequestException $e) {
				throw new PageFlowException($e->getMessage());
			}
		}
		return [];
	}
}