<?php
ignore_user_abort(true);
set_time_limit(0);

require_once '../backend/autoload.php';

use backend\Base;
use backend\Output;
use backend\CreateDataSet;

if(!Base::is_init())
	Output::error('ESMira is not ready!');

$rest_json = file_get_contents('php://input');
if(!($json = json_decode($rest_json)))
	Output::error('Unexpected data format');

if($_SERVER['REQUEST_METHOD'] !== 'POST')
	Output::error('No Data');

try {
	$dataSet = new CreateDataSet($json);
	Output::success(json_encode([
		'states' => $dataSet->output,
		'tokens' => $dataSet->new_studyTokens
	]));
}
catch(Exception $e) {
	Output::error($e->getMessage());
}