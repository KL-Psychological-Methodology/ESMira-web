<?php

namespace phpClasses\noJs\pages;

use phpClasses\noJs\Lang;
use phpClasses\noJs\Page;

class ChangeLang implements Page {
	public function getTitle() {
		return Lang::get('language');
	}
	
	public function getContent() {
		return '<a class="vertical verticalPadding" href="?lang=de">&#127465;&#127466; Deutsch</a>
		<a class="vertical verticalPadding" href="?lang=en">&#127468;&#127463; English</a>';
	}
}