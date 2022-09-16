import {FILE_ADMIN} from "../variables/urls";
import {PromiseCache} from "../main_classes/promise_cache";
import {OwnMapping} from "../helpers/knockout_own_mapping";
import {Defaults} from "../variables/defaults";

export let listUrl = FILE_ADMIN+"?type=list_accounts";

export function load_accountData() {
	return PromiseCache.loadJson(listUrl, function(data) {
		let accountList = [];
		for(let i = 0, max = data.length; i < max; ++i) {
			accountList.push(OwnMapping.fromJS(data[i], Defaults.account));
		}
		
		return accountList;
	});
}