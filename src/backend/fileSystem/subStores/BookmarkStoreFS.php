<?php 

namespace backend\fileSystem\subStores;

use backend\fileSystem\loader\BookmarksLoader;
use backend\fileSystem\PathsFS;
use backend\subStores\BookmarkStore;

class BookmarkStoreFS implements BookmarkStore {
    const KEY_URL = "url";
    const KEY_ALIAS = "alias";

    public function getBookmarksUser(string $accountName): array {
        $bookmarks = BookmarksLoader::importFile();
        return $bookmarks[$accountName] ?? [];
    }

    public function setBookmark(string $accountName, string $url, string $alias): array {
        $bookmarks = BookmarksLoader::importFile();
        if(!isset($bookmarks[$accountName]))
            $bookmarks[$accountName] = [];
        $newBookmark = [self::KEY_URL => $url, self::KEY_ALIAS => $alias];
        $didReplace = false;
        foreach($bookmarks[$accountName] as $bookmark) {
            if(strcmp($bookmark[self::KEY_URL], $url) == 0){
                $bookmark[self::KEY_ALIAS] = $alias;
                $didReplace = true;
                break;
            }
        }
        if(!$didReplace)
            $bookmarks[$accountName][] = $newBookmark;
        BookmarksLoader::exportFile($bookmarks);
        return $newBookmark;
    }

    public function deleteBookmark(string $accountName, string $url) {
        $bookmarks = BookmarksLoader::importFile();
        if(!isset($bookmarks[$accountName]))
            return;
        
        $index = array_search($url, array_column($bookmarks[$accountName], self::KEY_URL));
        if($index === false)
            return;
        array_splice($bookmarks[$accountName], $index, 1);
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