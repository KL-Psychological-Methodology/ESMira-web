<?php
declare(strict_types=1);

namespace backend;

class DataSetCacheContainer {
	/**
	 * The ids of the cached data.
	 * @var int[]
	 */
	public $ids = [];
	/**
	 * The cached data, stored in the same order as the ids array.
	 * @var array[]
	 */
	public $data = [];
	
	public function __construct(int $datasetId, /*mixed*/ $data) {
		$this->add($datasetId, $data);
	}
	public function add(int $datasetId, /*mixed*/ $data) {
		if(!in_array($datasetId, $this->ids))
			$this->ids[] = $datasetId;
		$this->data[] = $data;
	}
}