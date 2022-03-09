<?php

namespace backend\admin\features\noPermission;

use backend\Output;

class DataFolderExists extends InitESMiraPrep {
	
	function exec() {
		$dataFolder_path = $this->assemble_data_folderPath($_POST['data_location']);
		
		$output = ['dataFolder_exists' => file_exists($dataFolder_path)];
		Output::successObj($output);
	}
}