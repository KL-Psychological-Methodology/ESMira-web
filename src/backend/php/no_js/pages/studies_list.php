<?php
require_once 'php/no_js/nojs_fu.php';
require_once 'php/files.php';
$access_key = get_accessKey();
?>


<div class="page_title">
	<?php echo $LANG->studies; ?>
</div>
<div class="page_content">
	<form method="get" action="" class="access_key_box">
		<label class="no_desc">
			<small><?php echo $LANG->accessKey; ?></small>
			<input type="hidden" name="studies"/>
			<input name="key" type="text" value="<?php if($access_key) echo htmlentities($access_key); ?>">
			<input type="submit" value="<?php echo $LANG->send; ?>"/>
		</label>
	</form>
	
	<div>
		<?php
		function index_study($s, $access_key) {
			$json_values = json_decode($s);
			
			$study_id = $json_values->id;
			
			
			echo "<div class=\"vertical verticalPadding\">
					<a href=\"?"
						.(isset($json_values->publishedWeb) && !$json_values->publishedWeb ? 'app_install&' : '')
						.($access_key ? "key=$access_key&" : '')
						."id=$study_id\">".htmlspecialchars($json_values->title).'</a>
				</div>';
		}
		
		function list_fromIndex($key) {
			$key_index = unserialize(file_get_contents(FILE_STUDY_INDEX));
			if(isset($key_index[$key])) {
				$ids = $key_index[$key];
				
				foreach($ids as $id) {
					$path = get_file_studyConfig($id);
					if(file_exists($path))
						index_study(file_get_contents($path), $key);
				}
			}
		}
		
		list_fromIndex($access_key ? $access_key : '~open');
		?>
	</div>
</div>