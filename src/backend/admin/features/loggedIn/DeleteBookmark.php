<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class DeleteBookmark extends IsLoggedIn {
    function exec(): array {
        if(!isset($_POST['url']))
           throw new PageFlowException('Missing data');

        $url = $_POST['url'];
        $accountName = Permission::getAccountName();

        Configs::getDataStore()->getBookmarkStore()->deleteBookmark($accountName, $url);
        
        return [$url];
    }
}