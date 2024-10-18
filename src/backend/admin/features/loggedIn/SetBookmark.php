<?php

namespace backend\admin\features\loggedIn;

use backend\admin\IsLoggedIn;
use backend\Configs;
use backend\exceptions\PageFlowException;
use backend\Permission;

class SetBookmark extends IsLoggedIn {
    function exec(): array {
        if(!isset($_POST['url']) || !isset($_POST['alias']))
           throw new PageFlowException('Missing data');

        $alias = $_POST['alias'];
        $url = $_POST['url'];
        $accountName = Permission::getAccountName();

        return Configs::getDataStore()->getBookmarkStore()->setBookmark($accountName, $url, $alias);
    }
}