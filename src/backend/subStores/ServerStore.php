<?php

namespace backend\subStores;

use backend\CriticalError;

interface ServerStore {
	public function getImpressum(string $langCode): string;
	/**
	 * @throws CriticalError
	 */
	public function saveImpressum(string $impressum, string $langCode);
	/**
	 * @throws CriticalError
	 */
	public function deleteImpressum(string $langCode);
	
	public function getPrivacyPolicy(string $langCode): string;
	/**
	 * @throws CriticalError
	 */
	public function savePrivacyPolicy(string $privacyPolicy, string $langCode);
	/**
	 * @throws CriticalError
	 */
	public function deletePrivacyPolicy(string $langCode);
	
	public function getMediaFolderPath(int $studyId): string;
}