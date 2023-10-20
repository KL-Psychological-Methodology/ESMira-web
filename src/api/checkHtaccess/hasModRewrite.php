<?php
require_once '../../backend/autoload.php';

use backend\Configs;
use backend\JsonOutput;

if(Configs::getDataStore()->isInit())
	echo JsonOutput::error('Disabled');
else
	echo JsonOutput::successObj( [
		'htaccess' => true,
		'modRewrite' => true
	]);