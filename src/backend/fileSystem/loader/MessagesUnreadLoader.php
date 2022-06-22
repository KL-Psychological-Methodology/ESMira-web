<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\PathsFS;

class MessagesUnreadLoader {
	use MessagesLoader;
	protected static function getPath(int $studyId, string $userId): string {
		return PathsFS::fileMessageUnread($studyId, $userId);
	}
}