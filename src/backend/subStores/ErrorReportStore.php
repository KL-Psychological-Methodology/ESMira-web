<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use backend\dataClasses\ErrorReportInfo;
use backend\exceptions\PageFlowException;

interface ErrorReportStore {
	public function hasErrorReports(): bool;
	
	public function getList(): array;
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function getErrorReport(int $timestamp): string;
	public function saveErrorReport(string $msg): bool;
	
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function changeErrorReport(ErrorReportInfo $errorReportInfo);
	
	/**
	 * @throws \backend\exceptions\CriticalException
	 */
	public function removeErrorReport(int $timestamp);
}