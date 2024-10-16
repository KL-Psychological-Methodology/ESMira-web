<?php 

namespace backend\fileSystem\subStores;

use backend\fileSystem\loader\BookmarksLoader;
use backend\fileSystem\PathsFS;
use backend\subStores\BookmarkStore;

class BookmarkStoreFS implements BookmarkStore {
    public function getBookmarksUser(string $accountName): array {
        $bookmarks = BookmarksLoader::importFile();
        return $bookmarks[$accountName] ?? [];
    }

    public function setBookmark(string $accountName, string $url, string $name) {
        $bookmarks = BookmarksLoader::importFile();
        if(!isset($bookmarks[$accountName]))
            $bookmarks[$accountName] = [];
        $bookmarks[$accountName][$url] = $name;
        BookmarksLoader::exportFile($bookmarks);
    }

    public function deleteBookmark(string $accountName, string $url) {
        $bookmarks = BookmarksLoader::importFile();
        if(!isset($bookmarks[$accountName]))
            return;
        unset($bookmarks[$accountName][$url]);
        BookmarksLoader::exportFile($bookmarks);
    }

    public function deleteBookmarksUser(string $accountName) {
        $bookmarks = BookmarksLoader::importFile();
        unset($bookmarks[$accountName]);
        BookmarksLoader::exportFile($bookmarks);
    }
    
    public function changeUser(string $oldAccountName, string $newAccountName) {
        $bookmarks = BookmarksLoader::importFile();
        $bookmarks[$newAccountName] = $bookmarks[$oldAccountName];
        unset($bookmarks[$oldAccountName]);
        BookmarksLoader::exportFile($bookmarks);
    }
}