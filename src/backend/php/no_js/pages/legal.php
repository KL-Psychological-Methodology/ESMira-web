<div class="page_top page_title">
	<?php echo $LANG->impressum; ?>
</div>
<div class="page_content">
	<?php
	$lang = get_lang('_');
	
	$file_default_impressum = get_file_langImpressum('_');
	$file_lang_impressum = get_file_langImpressum($lang);
	if(file_exists($file_lang_impressum)) {
		echo '<div class="title-row">'.$LANG->impressum.'</div>';
		echo file_get_contents($file_lang_impressum);
	}
	else if(file_exists($file_default_impressum)) {
		echo '<div class="title-row">'.$LANG->impressum.'</div>';
		echo file_get_contents($file_default_impressum);
	}
	
	
	$file_default_privacyPolicy = get_file_langPrivacyPolicy('_');
	$file_lang_privacyPolicy = get_file_langPrivacyPolicy($lang);
	if(file_exists($file_lang_privacyPolicy)) {
		echo '<br/><br/><div class="title-row">'.$LANG->privacyPolicy.'</div>';
		echo file_get_contents($file_lang_privacyPolicy);
	}
	else if(file_exists($file_default_privacyPolicy)) {
		echo '<br/><br/><div class="title-row">'.$LANG->privacyPolicy.'</div>';
		echo file_get_contents($file_default_privacyPolicy);
	}
	?>
</div>
