<?php

if(!isset($study) || !isset($study_id))
	return;

if(isset($study->publishedWeb) && !$study->publishedWeb) {
	require 'app_install.php';
	return;
}
?>

<div class="page_title">
	<?php echo $LANG->questionnaires; ?>
</div>
<div class="page_content">
<?php
foreach($study->questionnaires as $index => $questionnaire) {
	if(!questionnaire_isActive($questionnaire))
		continue;
	
	$name = isset($questionnaire->name) ? $questionnaire->name : '';
	if(isset($access_key))
		echo "<a class=\"vertical verticalPadding\" href=\"?key=$access_key&id=$study_id&g=$index\">$name</a>";
	else
		echo "<a class=\"vertical verticalPadding\" href=\"?id=$study_id&g=$index\">$name</a>";
}
?>
</div>