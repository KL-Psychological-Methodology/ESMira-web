<?php
$studyData = get_studyData();
if(isset($studyData['notFound'])) {
	if(isset($studyData['error']))
		show_error($studyData['error']);
	return require 'studies_list.php';
}
$study = $studyData['study'];
?>

<div class="page_top page_title">
	<?php echo $LANG->informed_consent; ?>
</div>
<div class="page_content">
	<p>
		<?php
		if($study != null && isset($study->informedConsentForm))
			echo $study->informedConsentForm;
		?>
	</p>
	<form method="post" action="" class="center">
		<input type="submit" name="informed_consent" value="<?php echo $LANG->i_agree; ?>"/>
	</form>
</div>