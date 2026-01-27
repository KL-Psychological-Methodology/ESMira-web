<?php

/*
Reason for update:
	3.4.3 extracts rewards to their own permission. In order to preserve backwards compatibility, the following steps need to be taken:
		1. update metadata of all studies, so the metadata includes information on whether the reward system is active.
		2. assign reward permission to all users who previously held read permission for a study.
*/


// 1. Update Metadata

use backend\Configs;

$studyStore = Configs::getDataStore()->getStudyStore();
$studies = $studyStore->getStudyIdList();

foreach($studies as $studyId) {
	$study = $studyStore->getStudyConfig($studyId);

	$studyMetadataStore = Configs::getDataStore()->getStudyMetadataStore($studyId);
	$studyMetadataStore->updateMetadata($study);
}

// 2. Assign Reward Permissions

$accountStore = Configs::getDataStore()->getAccountStore();
$accountList = $accountStore->getAccountList();

foreach($accountList as $account) {
	$permissions = $accountStore->getPermissions($account);
	$readPermissions = isset($permissions['read']) ? $permissions['read'] : [];
	foreach($readPermissions as $studyId) {
		$accountStore->addStudyPermission($account, $studyId, 'reward');
	}
}