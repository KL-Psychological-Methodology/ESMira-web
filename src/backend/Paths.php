<?php

namespace backend;

class Paths {
    const SUB_PATH_CONFIG = 'backend/config/configs.php';
    const FILE_CONFIG = DIR_BASE . Paths::SUB_PATH_CONFIG;
    const FILENAME_MEDIA_ZIP = 'media.zip';
    const FOLDER_SERVER_BACKUP = DIR_BASE .'backup/';
    const FOLDER_SERVER_UPDATE = DIR_BASE .'update/';
    const FILE_SERVER_UPDATE = DIR_BASE .'update.zip';
    const FILE_DEFAULT_CONFIG = DIR_BASE . 'backend/defaults/configs.default.php';
    const FILENAME_VERSION = 'VERSION';
    const FILENAME_STRUCTURE = 'STRUCTURE';
    const FILE_SERVER_VERSION = DIR_BASE . Paths::FILENAME_VERSION;
    const FILE_STRUCTURE = DIR_BASE . Paths::FILENAME_STRUCTURE;
    
    
    public static function folderImages(int $studyId): string {
        return self::folderMedia($studyId) . 'images/';
    }
    public static function folderAudio(int $studyId): string {
        return self::folderMedia($studyId) . 'audio/';
    }
    public static function folderMedia(int $studyId): string {
        return Configs::getDataStore()->getServerStore()->getMediaFolderPath($studyId);
    }
    
    
    public static function publicFileMedia(string $userId, int $entryId, string $key): string {
        return "$userId/$key-$entryId";
    }
    public static function fileMediaZip(int $studyId): string {
        return self::folderMedia($studyId) . Paths::FILENAME_MEDIA_ZIP;
    }
    
    public static function publicFileAudioFromFileName(string $fileName): string {
        return 'audio/' . $fileName . '.3gpp';
    }
    public static function publicFileAudioFromData(string $userId, int $entryId, string $key): string {
        return self::publicFileAudioFromFileName(self::publicFileMedia($userId, $entryId, $key));
    }
    public static function publicFileAudioFromMediaFilename(string $fileName): string {
        return self::publicFileAudioFromFileName(self::getFromUrlFriendly($fileName));
    }
    public static function fileAudioFromData(int $studyId, string $userId, int $entryId, string $key): string {
        return self::folderAudio($studyId) . Paths::makeUrlFriendly(Paths::publicFileMedia($userId, $entryId, $key));
    }
    
    public static function publicFileImageFromFileName(string $fileName): string {
        return 'images/' . $fileName . '.png';
    }
    public static function publicFileImageFromData(string $userId, int $entryId, string $key): string {
        return self::publicFileImageFromFileName(self::publicFileMedia($userId, $entryId, $key));
    }
    public static function publicFileImageFromMediaFilename(string $fileName): string {
        return self::publicFileImageFromFileName(self::getFromUrlFriendly($fileName));
    }
    public static function fileImageFromData(int $studyId, string $userId, int $entryId, string $key): string {
        return self::folderImages($studyId) . Paths::makeUrlFriendly(Paths::publicFileMedia($userId, $entryId, $key));
    }
    
    
	/**
	 * Decodes a URL-friendly formatted string by reversing the Base64 encoding process
	 * and replacing URL-safe characters with their original counterparts.
	 * @see https://www.php.net/manual/en/function.base64-encode.php#123098
	 *
	 * @param string $s The URL-friendly formatted string to be decoded.
	 * @return string The original decoded string.
	 */
	public static function getFromUrlFriendly(string $s): string {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $s));
    }
	
	/**
	 * Converts a given string into a URL-friendly format by encoding it using Base64 and replacing
	 * certain characters with URL-safe alternatives.
	 *
	 * @param string $s The input string to be converted into a URL-friendly format.
	 * @return string The URL-friendly representation of the input string.
	 */
	public static function makeUrlFriendly(string $s): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($s));
    }
	
	/**
	 * Removes all non-word characters from the provided string, ensuring the string is safe to use.
	 *
	 * @param string $s The input string to be sanitized.
	 * @return string The sanitized string with non-word characters removed.
	 */
    public static function makeSafe(string $s): string {
        return preg_replace('/\W/', '', $s);
    }
}