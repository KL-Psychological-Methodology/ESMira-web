<?php

use backend\JsonOutput;
use backend\noJs\ForwardingException;
use backend\noJs\pages\AppInstall;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';

try {
	$page = new AppInstall();
}
catch(ForwardingException $e) {
	echo JsonOutput::successObj([
		'forwarded' => true,
		'pageHtml' => '',
		'pageTitle' => '',
	]);
	return;
}
catch(Throwable $e) {
	echo JsonOutput::error($e->getMessage());
	return;
}
echo JsonOutput::successObj([
	'pageHtml' => $page->getContent(),
	'pageTitle' => $page->getTitle(),
]);