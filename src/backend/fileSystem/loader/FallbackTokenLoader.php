<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class FallbackTokenLoader
{
	private static $setupCache = null;
	private static $inboundCache = null;
	private static $outboundCache = null;

	public static function importInboundFile(): array
	{
		if (self::$inboundCache)
			return self::$inboundCache;
		$path = PathsFS::fileIncomingFallbackTokens();
		return self::$inboundCache = file_exists($path) ? unserialize(file_get_contents($path)) : [];
	}

	public static function exportInboundFile(array $inboundTokens)
	{
		$path = PathsFS::fileIncomingFallbackTokens();
		FileSystemBasics::writeFile($path, serialize($inboundTokens));
		self::$inboundCache = $inboundTokens;
	}

	public static function importOutboundFile(): array
	{
		if (self::$outboundCache)
			return self::$outboundCache;
		$path = PathsFS::fileOutboundFallbackTokens();
		return self::$outboundCache = file_exists($path) ? unserialize(file_get_contents($path)) : [];
	}

	public static function exportOutboundFile(array $outboundTokens)
	{
		$path = PathsFS::fileOutboundFallbackTokens();
		FileSystemBasics::writeFile($path, serialize($outboundTokens));
		self::$outboundCache = $outboundTokens;
	}

	public static function importSetupFile(): array
	{
		if (self::$setupCache)
			return self::$setupCache;
		$path = PathsFS::fileFallbackSetupTokens();
		return self::$inboundCache = file_exists($path) ? unserialize(file_get_contents($path)) : [];
	}

	public static function exportSetupFile(array $setupTokens)
	{
		$path = PathsFS::fileFallbackSetupTokens();
		FileSystemBasics::writeFile($path, serialize($setupTokens));
		self::$setupCache = $setupTokens;
	}
}