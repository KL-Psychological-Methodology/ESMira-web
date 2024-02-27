<?php
//because of .htaccess, this should never be called unless mod_rewrite is disabled
require_once '../../backend/autoload.php';

use backend\Configs;
use backend\JsonOutput;

if(Configs::getDataStore()->isInit())
	echo JsonOutput::error('Disabled');
else
	echo JsonOutput::successObj([
		'htaccess' => $_SERVER['HTACCESS_ENABLED'] ?? false,
		'modRewrite' => false
	]);