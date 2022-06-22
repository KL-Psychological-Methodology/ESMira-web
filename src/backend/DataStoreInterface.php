<?php
declare(strict_types=1);

namespace backend;

use backend\subStores\ErrorReportStore;
use backend\subStores\LoginTokenStore;
use backend\subStores\MessagesStore;
use backend\subStores\ResponsesStore;
use backend\subStores\ServerStore;
use backend\subStores\ServerStatisticsStore;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyMetadataStore;
use backend\subStores\StudyStatisticsMetadataStore;
use backend\subStores\StudyStatisticsStore;
use backend\subStores\StudyStore;
use backend\subStores\UserDataStore;
use backend\subStores\UserStore;

interface DataStoreInterface {
	public function isInit(): bool;
	
	public function getESMiraInitializer(): ESMiraInitializer;
	
	public function getUserStore(): UserStore;
	public function getLoginTokenStore(): LoginTokenStore;
	public function getMessagesStore(): MessagesStore;
	public function getStudyAccessIndexStore(): StudyAccessIndexStore;
	public function getStudyStore(): StudyStore;
	public function getResponsesStore(): ResponsesStore;
	public function getUserDataStore(string $userId): UserDataStore;
	/**
	 * @throws CriticalError
	 */
	public function getStudyMetadataStore(int $studyId): StudyMetadataStore;
	public function getStudyStatisticsMetadataStore(int $studyId): StudyStatisticsMetadataStore;
	public function getStudyStatisticsStore(int $studyId): StudyStatisticsStore;
	public function getServerStatisticsStore(): ServerStatisticsStore;
	public function getErrorReportStore(): ErrorReportStore;
	public function getServerStore(): ServerStore;
}