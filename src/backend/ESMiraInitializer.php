<?php

namespace backend;

use backend\exceptions\CriticalException;

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
	 */
	public function create($accountName, $password);
}