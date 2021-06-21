<?php
require_once 'php/files.php';
if(file_exists(FILE_PRIVACY_POLICY)) {
	echo '<div class="page_title">'.$LANG->privacyPolicy.'</div><div class="page_content">';
	readfile(FILE_PRIVACY_POLICY);
	echo '</div>';
}
?>