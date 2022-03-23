<?php

namespace backend\admin\features\noPermission;

use backend\admin\NoPermission;
use backend\Base;
use backend\Files;
use backend\Output;
use const DIR_BASE;

class InitESMiraPrep extends NoPermission {
	
	protected function assemble_data_folderPath($data_location) {
		$last_char = substr($data_location, -1);
		if($last_char !== '/' && $last_char !== '\\')
			$data_location .= '/';
		
		if(!file_exists($data_location))
			Output::error('The path you provided does not exist on the server');
		
		return $data_location .Files::FILENAME_DATA .'/';
	}
	
	function exec() {
		if(Base::is_init())
			Output::error('Disabled');
		
		Output::successObj([
			'dir_base' => DIR_BASE,
			'dataFolder_exists' => file_exists(self::assemble_data_folderPath(DIR_BASE))
		]);
	}
}