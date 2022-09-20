<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use backend\DataSetCache;
use backend\FileUploader;

interface ResponsesStore {
	public function saveWebAccessDataSet(int $studyId, int $timestamp, string $pageName, string $referer, string $userAgent): bool;
	public function saveDataSetCache(string $userId, DataSetCache $cache, callable $successProgressCallback, callable $errorProgressCallback);
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function uploadFile(int $studyId, string $userId, int $identifier, FileUploader $fileUploader);
	
	public function getLastResponseTimestampOfStudies(): array;
	public function createMediaZip(int $studyId);
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function outputResponsesFile(int $studyId, string $identifier);
	
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function outputImageFromResponses(int $studyId, string $userId, int $entryId, string $key);
	public function getResponseFilesList(int $studyId): array;
	
}