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
	<?php echo $LANG->user_id; ?>
</div>
<div class="page_content">
	<p>
		<?php
		echo $study != null && isset($study->chooseUsernameInstructions) ? $study->chooseUsernameInstructions : $LANG->default_chooseUsernameInstructions;
		?>
	</p>
	
	<form method="post" action="" class="center">
		<p>
			<label>
				<small><?php echo $LANG->user_id; ?></small>
				<input name="participant" type="text" value=""/>
			</label>
			<input name="new_participant" type="hidden" value="1"/>
			<input type="hidden" name="accept_informedConsent" value="1"/>
			<input type="submit" value="<?php echo $LANG->save; ?>"/>
		</p>
	</form>
</div>