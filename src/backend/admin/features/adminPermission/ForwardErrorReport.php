<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\exceptions\PageFlowException;

class ForwardErrorReport extends HasAdminPermission {
	function exec(): array {
		if (!isset($_POST['recipient']) || !isset($_POST['report'])) {
			throw new \backend\exceptions\PageFlowException("Missing data");
		}

		$context = stream_context_create([
			"http" => [
				"method" => "POST",
				"header" => "Content-type: application/x-www-form-urlencoded",
				"content" => $_POST['report']
			]
		]);
		$url = $_POST['recipient'] . "/api/save_errors.php";
		error_log($url);
		$response = file_get_contents($url, false, $context);
		if($response === false) 
			throw new PageFlowException("No response from recipient server");
		
		$response = json_decode($response, true);

		if(!($response['success'] ?? false)) {
			throw new PageFlowException("Recipient server returned error");
		}

		return [];
	}
}
