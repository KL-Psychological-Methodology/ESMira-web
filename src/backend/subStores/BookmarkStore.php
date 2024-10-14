<?php

namespace backend\subStores;

interface BookmarkStore {
    public function getBookmarksUser(string $accountName): array;
    public function deleteBookmarksUser(string $accountName);
    public function setBookmark(string $accountName, string $url, string $name);
    public function deleteBookmark(string $accountName, string $url);
}