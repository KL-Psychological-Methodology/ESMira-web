<?php
declare(strict_types=1);

namespace backend\fileSystem;

use backend\Configs;
use backend\DataStoreInterface;
use backend\ESMiraInitializer;
use backend\fileSystem\subStores\ErrorReportStoreFS;
use backend\fileSystem\subStores\LoginTokenStoreFS;
use backend\fileSystem\subStores\MessagesStoreFS;
use backend\fileSystem\subStores\ResponsesStoreFS;
use backend\fileSystem\subStores\RewardCodeStoreFS;
use backend\fileSystem\subStores\ServerStoreFS;
use backend\fileSystem\subStores\ServerStatisticsStoreFS;
use backend\subStores\ErrorReportStore;
use backend\subStores\LoginTokenStore;
use backend\subStores\MessagesStore;
use backend\subStores\ResponsesStore;
use backend\subStores\RewardCodeStore;
use backend\subStores\ServerStore;
use backend\subStores\ServerStatisticsStore;
use backend\subStores\StudyAccessIndexStore;
use backend\subStores\StudyMetadataStore;
use backend\subStores\StudyStore;
use backend\subStores\StudyStatisticsMetadataStore;
use backend\subStores\StudyStatisticsStore;
use backend\subStores\UserDataStore;
use backend\subStores\AccountStore;
use backend\fileSystem\subStores\AccountStoreFS;
use backend\fileSystem\subStores\BookmarkStoreFS;
use backend\fileSystem\subStores\MerlinLogsStoreFS;
use backend\fileSystem\subStores\StudyAccessIndexStoreFS;
use backend\fileSystem\subStores\StudyStoreFS;
use backend\fileSystem\subStores\UserDataStoreFS;
use backend\fileSystem\subStores\StudyMetadataStoreFS;
use backend\fileSystem\subStores\StudyStatisticsMetadataStoreFS;
use backend\fileSystem\subStores\StudyStatisticsStoreFS;
use backend\subStores\BookmarkStore;
use backend\subStores\MerlinLogsStore;

class DataStoreFS implements DataStoreInterface {
	public function isInit(): bool {
		$path = Configs::get('dataFolder_path');
		return $path && file_exists($path);
	}
	public function isReady(): bool {
		return disk_free_space(PathsFS::folderData()) > 5000000; //5 mb
	}
	
	public function getESMiraInitializer(): ESMiraInitializer {
		return new ESMiraInitializerFS();
	}
	
	public function getAccountStore(): AccountStore {
		return new AccountStoreFS();
	}
	public function getLoginTokenStore(): LoginTokenStore {
		return new LoginTokenStoreFS();
	}
	public function getMerlinLogsStore(): MerlinLogsStore{
		return new MerlinLogsStoreFS();
	}
	public function getMessagesStore(): MessagesStore {
		return new MessagesStoreFS();
	}
	public function getStudyAccessIndexStore(): StudyAccessIndexStore {
		return new StudyAccessIndexStoreFS();
	}
	public function getStudyStore(): StudyStore {
		return new StudyStoreFS();
	}
	public function getResponsesStore(): ResponsesStore {
		return new ResponsesStoreFS();
	}
	public function getUserDataStore(string $userId): UserDataStore {
		return new UserDataStoreFS($userId);
	}
	public function getStudyMetadataStore(int $studyId): StudyMetadataStore {
		return new StudyMetadataStoreFS($studyId);
	}
	public function getStudyStatisticsMetadataStore(int $studyId): StudyStatisticsMetadataStore {
		return new StudyStatisticsMetadataStoreFS($studyId);
	}
	public function getStudyStatisticsStore(int $studyId): StudyStatisticsStore {
		return new StudyStatisticsStoreFS($studyId);
	}
	public function getServerStatisticsStore(): ServerStatisticsStore {
		return new ServerStatisticsStoreFS();
	}
	public function getErrorReportStore(): ErrorReportStore {
		return new ErrorReportStoreFS();
	}
	public function getServerStore(): ServerStore {
		return new ServerStoreFS();
	}
	public function getRewardCodeStore(): RewardCodeStore {
		return new RewardCodeStoreFS();
	}
	public function getBookmarkStore(): BookmarkStore {
		return new BookmarkStoreFS();
	}
}