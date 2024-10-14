<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\Permission;

class GetBookmarks extends IsLoggedIn {
    function exec(): array {
        $accountName = Permission::getAccountName();
        return Configs::getDataStore()->getBookmarkStore()->getBookmarksUser($accountName);
    }
}