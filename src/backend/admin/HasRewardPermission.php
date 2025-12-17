<?php

namespace backend\admin;

use backend\exceptions\PageFlowException;
use backend\Permission;

abstract class HasRewardPermission extends IsLoggedIn {
    function __construct() {
        parent::__construct();
        if ($this->studyId == 0)
            throw new PageFlowException('Missing study id');
        if (!$this->isAdmin && !(Permission::hasPermission($this->studyId, 'reward')))
            throw new PageFlowException('No permission');
    }
}