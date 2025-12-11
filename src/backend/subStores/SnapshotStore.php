<?php

namespace backend\subStores;

use ZipArchive;


interface SnapshotStore {
	public function addDataToZip(ZipArchive $zip, callable $reportProgress): void;
	public function getSnapshotZipPath(string $snapshotName): string;
	public function restoreDataFromSnapshot(string $pathUpdate, string $pathBackup, callable $reportProgress): void;
	public function listSnapshots(): array;
}