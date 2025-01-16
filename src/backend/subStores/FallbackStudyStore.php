<?php

namespace backend\subStores;

interface FallbackStudyStore extends BaseStudyStore
{
	public function __construct(string $encodedUrl);

	public function deleteStore();
}
