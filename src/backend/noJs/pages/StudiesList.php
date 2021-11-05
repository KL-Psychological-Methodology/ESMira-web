<?php

namespace backend\noJs\pages;

use backend\Base;
use backend\Files;
use backend\noJs\Lang;
use backend\noJs\Page;

class StudiesList implements Page {
	static function index_study($s, $access_key) {
		$json_values = json_decode($s);
		
		$study_id = $json_values->id;
		
		return "<div class=\"vertical verticalPadding\">
					<a href=\"?"
			.(isset($json_values->publishedWeb) && !$json_values->publishedWeb ? 'app_install&' : '')
			.($access_key ? "key=$access_key&" : '')
			."id=$study_id\">".htmlspecialchars($json_values->title).'</a>
				</div>';
	}
	
	static function list_fromIndex($key) {
		$output = '';
		$key_index = unserialize(file_get_contents(Files::get_file_studyIndex()));
		if(isset($key_index[$key])) {
			$ids = $key_index[$key];
			
			foreach($ids as $id) {
				$path = Files::get_file_studyConfig($id);
				if(file_exists($path))
					$output .= self::index_study(file_get_contents($path), $key);
			}
		}
		return $output;
	}
	
	public function getTitle() {
		return Lang::get('studies');
	}
	
	public function getContent() {
		$access_key = Base::get_accessKey();
		return '<form method="get" action="" class="access_key_box">
			<label class="no_desc">
				<small>' .Lang::get('accessKey') .'</small>
				<input type="hidden" name="studies"/>
				<input name="key" type="text" value="' .($access_key ? htmlentities($access_key) : '') .'">
				<input type="submit" value="' .Lang::get('send') .'"/>
			</label>
		</form>
		<div>'
			.self::list_fromIndex($access_key ?: '~open')
		.'</div>';
	}
}