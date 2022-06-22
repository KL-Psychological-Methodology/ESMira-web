<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\PathsFS;

class MessagesPendingLoader {
	use MessagesLoader;
	protected static function getPath(int $studyId, string $userId): string {
		return PathsFS::fileMessagePending($studyId, $userId);
	}
}