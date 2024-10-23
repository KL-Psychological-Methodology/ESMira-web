<?php

namespace backend\subStores;

interface BookmarkStore {
    public function getBookmarksUser(string $accountName): array;
    public function deleteBookmarksUser(string $accountName);
    public function changeUser(string $oldAccountName, string $newAccountName);
    public function setBookmark(string $accountName, string $url, string $alias): array;
    public function deleteBookmark(string $accountName, string $url);
}