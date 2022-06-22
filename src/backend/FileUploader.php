<?php

namespace backend;

class FileUploader {
	/**
	 * @var array
	 */
	private $fileData;
	public function __construct(array $fileData) {
		$this->fileData = $fileData;
	}
	public function upload(string $targetPath): bool {
		return move_uploaded_file($this->fileData['tmp_name'], $targetPath);
	}
}