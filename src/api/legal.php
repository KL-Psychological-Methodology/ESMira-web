<?php
require_once '../backend/autoload.php';

use backend\Base;
use backend\Files;
use backend\Output;

if(!Base::is_init())
	Output::error('ESMira is not ready!');

$legal = [];

$lang = Base::get_lang('_');

$file_default_impressum = Files::get_file_langImpressum('_');
$file_lang_impressum = Files::get_file_langImpressum($lang);
if(file_exists($file_lang_impressum))
	$legal['impressum'] = file_get_contents($file_lang_impressum);
else if(file_exists($file_default_impressum))
	$legal['impressum'] = file_get_contents($file_default_impressum);

$file_default_privacyPolicy = Files::get_file_langPrivacyPolicy('_');
$file_lang_privacyPolicy = Files::get_file_langPrivacyPolicy($lang);
if(file_exists($file_lang_privacyPolicy))
	$legal['privacyPolicy'] = file_get_contents($file_lang_privacyPolicy);
else if(file_exists($file_default_privacyPolicy))
	$legal['privacyPolicy'] = file_get_contents($file_default_privacyPolicy);

Output::successObj($legal);