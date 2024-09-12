<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;
use backend\exceptions\CriticalException;
use Throwable;

class CreateSnapshot extends HasAdminPermission {

    function execAndOutput() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        echo "Start\n\n";

        if(ob_get_contents())
            ob_end_flush();
        flush();

        try{
            Configs::getDataStore()->getSnapshotStore()->createSnapshot();
        } catch(Throwable $e) {
            echo "event: failed\n";
            $msg = $e->getMessage();
            echo "data: $msg\n\n";
            if(ob_get_contents())
                ob_end_flush();
            flush();
            throw $e;
        }

        echo "event: finished\n";
        echo "data: \n\n";

        if(ob_get_contents())
            ob_end_flush();
        flush();
    }

    function exec(): array {
        throw new CriticalException('Internal error. CreateSnapshot can only be used with execAndOutput()');
    }
}