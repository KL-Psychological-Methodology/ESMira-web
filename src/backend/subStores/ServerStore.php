<?php

namespace backend\subStores;

use backend\exceptions\CriticalException;

interface ServerStore {
	public function getImpressum(string $langCode): string;
	/**
	 * @throws CriticalException
	 */
	public function saveImpressum(string $impressum, string $langCode);
	/**
	 * @throws CriticalException
	 */
	public function deleteImpressum(string $langCode);
	
	
	public function getPrivacyPolicy(string $langCode): string;
	/**
	 * @throws CriticalException
	 */
	public function savePrivacyPolicy(string $privacyPolicy, string $langCode);
	/**
	 * @throws CriticalException
	 */
	public function deletePrivacyPolicy(string $langCode);
	
	
	public function getHomeMessage(string $langCode): string;
	/**
	 * @throws CriticalException
	 */
	public function saveHomeMessage(string $homeMessage, string $langCode);
	/**
	 * @throws CriticalException
	 */
	public function deleteHomeMessage(string $langCode);
	
	public function getMediaFolderPath(int $studyId): string;
}