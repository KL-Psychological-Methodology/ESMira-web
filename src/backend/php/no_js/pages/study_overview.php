<?php
require_once 'php/no_js/nojs_fu.php';

$studyData = get_studyData();
if(isset($studyData['notFound'])) {
	if(isset($studyData['error']))
		show_error($studyData['error']);
	return require 'studies_list.php';
}
$study = $studyData['study'];
$study_id = $study->id;

$access_key = $studyData['accessKey'];

if(!isset($_GET['ref']))
	save_webAccess($study_id, 'navigatedFromHome_noJs');

?>
<div class="page_top page_title">
	<div class="title">
		<?php echo isset($study->title) ? $study->title : 'Study'; ?>
	</div>
	<div class="extra">
		<?php if(isset($study->contactEmail)) echo '<a class="small_text" href="mailto:'.$study->contactEmail.'">' .$LANG->contactEmail .'</a>'; ?>
	</div>
</div>
<div class="page_content">
	<div>
		<?php
		if(isset($study->studyDescription) && strlen($study->studyDescription))
			echo '<div class="scrollBox">' .$study->studyDescription .'</div>';
		?>
		<br/>
		<div class="title-row"><?php echo $LANG->colon_questionnaires; ?></div>
		<?php
		foreach($study->questionnaires as $index => $questionnaire) {
			if(!questionnaire_isActive($questionnaire))
				continue;
			
			$name = isset($questionnaire->title) ? $questionnaire->title : $LANG->questionnaire;
			$qId = $questionnaire->internalId;
			if($access_key)
				echo "<a class=\"vertical verticalPadding\" href=\"?key=$access_key&id=$study_id&qid=$qId\">$name</a>";
			else
				echo "<a class=\"vertical verticalPadding\" href=\"?id=$study_id&qid=$qId\">$name</a>";
		}
		?>
	</div>

</div>