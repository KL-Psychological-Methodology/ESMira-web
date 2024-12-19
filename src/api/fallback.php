<?php

ignore_user_abort(true);
set_time_limit(0);

require_once dirname(__FILE__, 2) . '/backend/autoload.php';

use backend\exceptions\CriticalException;
use backend\exceptions\FallbackFeatureException;
use backend\exceptions\PageFlowException;
use backend\FallbackRequest;
use backend\FallbackRequestOutput;
use backend\JsonOutput;

if (!isset($_GET['type'])) {
	echo JsonOutput::error('Missing data');
	return;
}

$classIndex = [
	// Setup

	'Setup' => '\backend\fallback\features\Setup',

	// Fallback Features

	'Ping' => '\backend\fallback\features\Ping',
	'DeleteStudy' => '\backend\fallback\features\DeleteStudy',
	'DeleteAllStudiesExcept' => '\backend\fallback\features\DeleteAllStudiesExcept',
	'FreezeStudy' => '\backend\fallback\features\FreezeStudy',
	'SaveStudy' => '\backend\fallback\features\SaveStudy',
];

$type = $_GET['type'];

if (!isset($classIndex[$type])) {
	echo FallbackRequestOutput::noSuccess(FallbackRequest::UNEXPECTED_REQUEST);
	return;
}
try {
	$className = $classIndex[$type];
	$c = new $className;
	$c->execAndOutput();
} catch (FallbackFeatureException $e) {
	switch ($e->getCode()) {
		case FallbackFeatureException::KEY_MISSING_FROM_REQUEST:
			echo FallbackRequestOutput::noSuccess(FallbackRequest::KEY_MISSING_FROM_REQUEST);
			return;
		case FallbackFeatureException::KEY_NOT_FOUND:
			echo FallbackRequestOutput::noSuccess(FallbackRequest::KEY_NOT_FOUND);
			return;
	}
	echo FallbackRequestOutput::noSuccess(FallbackRequest::UNKNOWN);
	return;
} catch (CriticalException $e) {
	echo FallbackRequestOutput::error($e->getMessage());
} catch (PageFlowException $e) {
	echo FallbackRequestOutput::error($e->getMessage());
}
