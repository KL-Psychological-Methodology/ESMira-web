<?php

use backend\Configs;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\JsonOutput;
use backend\Main;
use backend\noJs\ForwardingException;
use backend\noJs\NoJsMain;
use backend\noJs\pages\AppInstall;
use backend\Permission;
use backend\QuestionnaireSaver;

require_once dirname(__FILE__, 2) .'/backend/autoload.php';


$page = new AppInstall();
echo JsonOutput::successObj([
	'pageHtml' => $page->getContent(),
	'pageTitle' => $page->getTitle(),
]);