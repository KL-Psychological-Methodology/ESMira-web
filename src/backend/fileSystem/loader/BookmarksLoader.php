<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\PathsFS;
use backend\FileSystemBasics;

class BookmarksLoader {
    private static $cache = null;
    public static function importFile(): array {
        if(self::$cache)
            return self::$cache;
        $path = PathsFS::fileBookmarks();
        return self::$cache = file_exists($path) ? unserialize(file_get_contents($path)) : [];
    }

    public static function exportFile(array $bookmarks) {
        $path = PathsFS::fileBookmarks();
        FileSystemBasics::writeFile($path, serialize($bookmarks));
        self::$cache = $bookmarks;
    }
}