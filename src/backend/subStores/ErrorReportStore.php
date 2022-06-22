<?php

namespace backend\subStores;

use backend\CriticalError;
use backend\dataClasses\ErrorReportInfo;
use backend\PageFlowException;

interface ErrorReportStore {
	public function hasErrorReports(): bool;
	
	public function getList(): array;
	/**
	 * @throws CriticalError
	 */
	public function getErrorReport(int $timestamp): string;
	public function saveErrorReport(string $msg): bool;
	
	/**
	 * @throws CriticalError
	 */
	public function changeErrorReport(ErrorReportInfo $errorReportInfo);
	
	/**
	 * @throws CriticalError
	 */
	public function removeErrorReport(int $timestamp);
}