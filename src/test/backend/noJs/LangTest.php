<?php

namespace test\backend\noJs;

use backend\noJs\Lang;
use test\testConfigs\BaseTestSetup;

require_once __DIR__ .'/../../../backend/autoload.php';

class LangTest extends BaseTestSetup {
	function test_with_base_languages() {
		Lang::init();
		$this->assertEquals('en', Lang::get('_code'));
	}
	function test_with_alternative_languages() {
		$this->setGet(['lang' => 'de']);
		Lang::init();
		$this->assertEquals('de', Lang::get('_code'));
	}
	function test_with_not_existing_languages() {
		$this->setGet(['lang' => 'notExisting']);
		Lang::init();
		$this->assertEquals('en', Lang::get('_code'));
	}
	function test_with_arguments() {
		Lang::init();
		$this->assertEquals('3.6... Not great. Not terrible.', Lang::get('%d.6... Not great. Not %s.', 3.6, 'terrible'));
	}
}