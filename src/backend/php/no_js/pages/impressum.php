<div class="page_title">
	
	<?php
	require_once 'php/files.php';
	
	echo $LANG->impressum;
	
	if(file_exists(FILE_PRIVACY_POLICY))
		echo '<div class="right extra"><a href="?privacyPolicy" class="highlight internal_link">'.$LANG->privacyPolicy.'</a></div>';
	?>
</div>
<div class="page_content">
	<?php
	if(file_exists(FILE_IMPRESSUM))
		readfile(FILE_IMPRESSUM);
	?>
</div>
