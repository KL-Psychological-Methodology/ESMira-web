<?php

namespace backend\fileSystem\subStores;

use backend\Main;
use backend\fileSystem\PathsFS;
use backend\subStores\ServerStatisticsStore;
use Throwable;

class ServerStatisticsStoreFS extends ServerStatisticsStore {
	public function getStatisticsAsJsonString(): string {
		if(file_exists(PathsFS::fileServerStatistics()))
			return file_get_contents(PathsFS::fileServerStatistics());
		else
			return json_encode($this->createNewStatisticsDataObj());
	}
	public function update(callable $callback) {
		$pathServerStatistics = PathsFS::fileServerStatistics();
		
		if(!file_exists($pathServerStatistics)) {
			file_put_contents($pathServerStatistics, json_encode($this->createNewStatisticsDataObj()), LOCK_EX);
			chmod($pathServerStatistics, 0666);
		}
		
		$handle = fopen($pathServerStatistics, 'r+');
		if(!$handle) {
			Main::report("Could not open $pathServerStatistics. Server statistics were not updated!");
			return;
		}
		if(!flock($handle, LOCK_EX))
			Main::report("Could not lock $pathServerStatistics. Data could be lost!");
		
		try {
			$json = json_decode(fread($handle, filesize($pathServerStatistics)));
		}
		catch(Throwable $e) {
			Main::report("Server statistics seem to be broken. Statistics were recreated. Error message:\n\n" .$e->getMessage());
			$json = $this->createNewStatisticsDataObj();
		}
		$statistics = new StatisticsStoreWriterFS(
			$json
		);
		
		if($callback($statistics) === false) {
			flock($handle, LOCK_UN);
			fclose($handle);
			return;
		}
		
		$newJson = json_encode($statistics->getStatisticsObj());
		
		if(fseek($handle, 0) == -1)
			Main::report("fseek() failed for server statistics. Server statistics were not updated.\n\nPrevious server statistics:\n$json\n\nNew server statistics: $newJson");
		else if(!ftruncate($handle, 0))
			Main::report("ftruncate() failed for server statistics. Server statistics might be broken now and will automatically be recreated next time data is added.\n\nPrevious server statistics:\n$json\n\nNew server statistics: $newJson");
		else if(!fwrite($handle, $newJson))
			Main::report("Could not write server statistics. Server statistics might be broken now and will automatically be recreated next time data is added.\n\nPrevious server statistics:\n$json\n\nNew server statistics: $newJson");
		fflush($handle);
		flock($handle, LOCK_UN);
		fclose($handle);
	}
}