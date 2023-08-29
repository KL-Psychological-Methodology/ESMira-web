<?php

namespace backend\noJs\pages;

use backend\Main;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\noJs\Lang;
use backend\noJs\Page;
use stdClass;

class StudiesList implements Page {
	static function studyToOutput(stdClass $study, string $accessKey): string {
		$study_id = $study->id;
		
		return "<div class=\"vertical verticalPadding\">
					<a href=\"?"
			.(isset($study->publishedWeb) && !$study->publishedWeb ? 'app_install&' : '')
			.($accessKey ? "key=$accessKey&" : '')
			."id=$study_id\">".htmlspecialchars($study->title).'</a>
				</div>';
	}
	
	/**
	 * @throws CriticalException
	 */
	static function listFromIndex(string $accessKey): string {
		$output = '';
		$lang = Main::getLang(false);
		
		$studyStore = Configs::getDataStore()->getStudyStore();
		$ids = Configs::getDataStore()->getStudyAccessIndexStore()->getStudyIds($accessKey);
		foreach($ids as $studyId) {
			$studyJsonString = $studyStore->getStudyLangConfig($studyId, $lang);
			$output .= self::studyToOutput($studyJsonString, $accessKey);
		}
		return $output;
	}
	
	public function getTitle(): string {
		return Lang::get('studies');
	}
	
	public function getContent(): string {
		$accessKey = Main::getAccessKey();
		return '<form method="get" action="" class="accessKeyBox">
			<label class="noDesc">
				<small>' .Lang::get('accessKey') .'</small>
				<input type="hidden" name="studies"/>
				<input name="key" type="text" value="' .($accessKey ? htmlentities($accessKey) : '') .'">
				<input type="submit" value="' .Lang::get('send') .'"/>
			</label>
		</form>
		<div>'
			.self::listFromIndex($accessKey)
		.'</div>';
	}
}