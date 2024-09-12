<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use backend\Main;

class UploadSnapshot extends HasAdminPermission {
    function exec(): array {

        Main::setHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        Main::setHeader('Last-Modified: ' . gmdate("D, d M Y H:i:s") . " GMT");
        Main::setHeader('Cache-Control: no-store, no-cache, must-revalidate');
        Main::setHeader('Cache-Control: post-check=0, pre-check=0', false);
        Main::setHeader('Pragma: no-cache');

        @set_time_limit(5 * 60);

        $fileName = $_POST["name"];

        $snapshotStore = Configs::getDataStore()->getSnapshotStore();
        $snapshotStore->clearUploads($fileName);
        $snapshotStore->storeUploadPart($_FILES["file"]["tmp_name"], $fileName);

        if(isset($_POST["complete"]))
            $snapshotStore->completeUpload($fileName);

        return [];
    }
}