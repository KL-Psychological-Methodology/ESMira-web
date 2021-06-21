import {PromiseCache} from "../main_classes/promise_cache";
import {FILE_ADMIN} from "../variables/urls";
import ko from "knockout";

let userWithMessages = null;
export function loadMessages(study_id) {
	return PromiseCache.loadJson(FILE_ADMIN + "?type=list_userWithMessages&study_id="+study_id, function(msgs) {
		msgs.sort(function(a,b) {
			return a["lastMsg"] === b["lastMsg"] ? 0 : (a["lastMsg"] < b["lastMsg"] ? 1 : -1);
		});
		if(!userWithMessages)
			userWithMessages = ko.observableArray(msgs);
		else
			userWithMessages(msgs);
		return userWithMessages;
	});
}

export function reloadMessages(study_id) {
	PromiseCache.remove(FILE_ADMIN + "?type=list_userWithMessages&study_id="+study_id);
	return loadMessages(study_id);
}