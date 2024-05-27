<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;
use backend\dataClasses\MerlinLogInfo;

interface MerlinLogsStore {
    public function getStudiesWithUnreadMerlinLogsForPermission(): array;
    /**
     * @throws CriticalException
     */
    public function getMerlinLogsList(int $studyId): array;
    /**
     * @throws CriticalException
     */
    public function getMerlinLog(int $studyId, int $timestamp): string;
    /**
     * @throws CriticalException
     */
    public function receiveMerlinLog(int $studyId, string $msg): bool;
    /**
     * @throws CriticalException
     */
    public function changeMerlinLog(int $studyId, MerlinLogInfo $merlinLogInfo);
    /**
     * @throws CriticalException
     */
    public function removeMerlinLog(int $studyId, int $timestamp);
}