<?php
header('Content-Type: application/json;charset=UTF-8');
require_once 'php/global_json.php';
require_once 'php/files.php';
require_once 'php/basic_fu.php';

$legal = [];

$lang = get_lang();

$file_default_impressum = get_file_langImpressum('_');
$file_lang_impressum = get_file_langImpressum($lang);
if(file_exists($file_lang_impressum))
	$legal['impressum'] = file_get_contents($file_lang_impressum);
else if(file_exists($file_default_impressum))
	$legal['impressum'] = file_get_contents($file_default_impressum);

$file_default_privacyPolicy = get_file_langPrivacyPolicy('_');
$file_lang_privacyPolicy = get_file_langPrivacyPolicy($lang);
if(file_exists($file_lang_privacyPolicy))
	$legal['privacyPolicy'] = file_get_contents($file_lang_privacyPolicy);
else if(file_exists($file_default_privacyPolicy))
	$legal['privacyPolicy'] = file_get_contents($file_default_privacyPolicy);

success(json_encode($legal));
?>