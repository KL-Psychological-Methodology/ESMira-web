<?php

namespace backend\noJs\pages;

use backend\noJs\Lang;
use backend\noJs\Page;

class ChangeLang implements Page {
	public function getTitle(): string {
		return Lang::get('language');
	}
	
	public function getContent(): string {
		return '<a class="vertical verticalPadding" href="?lang=de">Deutsch</a>
		<a class="vertical verticalPadding" href="?lang=en">English</a>
		<a class="vertical verticalPadding" href="?lang=uk">українська</a>';
	}
}