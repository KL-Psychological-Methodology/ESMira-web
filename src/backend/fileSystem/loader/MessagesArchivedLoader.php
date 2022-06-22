<?php

namespace backend\fileSystem\loader;

use backend\fileSystem\PathsFS;

class MessagesArchivedLoader {
	use MessagesLoader;
	protected static function getPath(int $studyId, string $userId): string {
		return PathsFS::fileMessageArchive($studyId, $userId);
	}
}