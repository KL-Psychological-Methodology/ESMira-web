<?php

namespace backend;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;

interface ESMiraInitializer {
	/**
	 * @throws CriticalException
	 */
	public function getConfigAdditions(): array;
	/**
	 * @throws CriticalException
	 */
	public function getInfoArray(string $dataFolderBase = DIR_BASE): array;
	
	/**
	 * @throws CriticalException
	 * @throws PageFlowException
	 */
	public function create($accountName, $password);
}