<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class SetBookmark extends IsLoggedIn {
    function exec(): array {
        if(!isset($_POST['url']) || !isset($_POST['name']))
           throw new PageFlowException('Missing data');

        $name = $_POST['name'];
        $url = $_POST['url'];
        $accountName = Permission::getAccountName();

        Configs::getDataStore()->getBookmarkStore()->setBookmark($accountName, $url, $name);
        
        return [$url];
    }
}