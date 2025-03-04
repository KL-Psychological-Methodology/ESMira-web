<?php

namespace backend\admin\features\adminPermission;

use backend\admin\HasAdminPermission;
use backend\Configs;

class RebuildStudyIndex extends HasAdminPermission {
    function exec(): array {
        $dataStore = Configs::getDataStore();
        $studyStore = $dataStore->getStudyStore();

        $studyIdList = $studyStore->getStudyIdList();

        $indexStore = $dataStore->getStudyAccessIndexStore();
        $indexStore->reset();

        foreach ($studyIdList as $id) {
            $metadataStore = $dataStore->getStudyMetadataStore($id);
            if (!$metadataStore->isPublished()) {
                continue;
            }
            $accessKeys = $metadataStore->getAccessKeys();
            if (!$accessKeys) {
                $indexStore->add($id);
            } else {
                foreach ($accessKeys as $key) {
                    $indexStore->add($id, $key);
                }
            }
            $study = $studyStore->getStudyConfig($id);
            $indexStore->addQuestionnaireKeys($study);
        }
        $indexStore->saveChanges();

        return [];
    }
}
