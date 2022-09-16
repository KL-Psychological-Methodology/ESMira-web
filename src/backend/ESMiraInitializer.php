<?php

namespace backend;

interface ESMiraInitializer {
	/**
	 * @throws CriticalError
	 */
	public function getConfigAdditions(): array;
	/**
	 * @throws CriticalError
	 */
	public function getInfoArray(string $dataFolderBase = DIR_BASE): array;
	
	/**
	 * @throws CriticalError
	 */
	public function create($accountName, $password);
}