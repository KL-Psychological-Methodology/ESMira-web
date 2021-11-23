<?php
require_once '../../backend/autoload.php';

use backend\Output;
$output = [
	'htaccess' => true,
	'mod_rewrite' => true
];

Output::successObj($output);