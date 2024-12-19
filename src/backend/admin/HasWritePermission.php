<?php

namespace backend\admin;

use backend\Configs;
use backend\exceptions\FallbackRequestException;
use backend\exceptions\PageFlowException;
use backend\FallbackRequest;
use backend\Main;
use backend\Permission;

require_once DIR_BASE . 'backend/responseFileKeys.php';

abstract class HasWritePermission extends IsLoggedIn
{

	protected function handleFallback(string $feature, array $data = [])
	{
		$data['studyId'] = $this->studyId;
		$outboundUrls = Configs::getDataStore()->getFallbackTokenStore()->getOutboundTokenUrls();
		foreach ($outboundUrls as $url) {
			$request = new FallbackRequest();
			try {
				$request->postRequest($url['url'], $feature, $data);
			} catch (FallbackRequestException $e) {
				$errorMsg = "Fallback System encountered an error when trying to call feature " . $feature . " on url '" . $url['url'] . "': ";
				Main::reportError($e, $errorMsg);
			}
		}
	}

	function __construct()
	{
		parent::__construct();
		if ($this->studyId == 0)
			throw new PageFlowException('Missing study id');
		if (
			!$this->isAdmin
			&& !Permission::hasPermission($this->studyId, 'write')
			&& (!Permission::canCreate() || Configs::getDataStore()->getStudyStore()->studyExists($this->studyId))
		) {
			throw new PageFlowException('No permission');
		}
	}
}
