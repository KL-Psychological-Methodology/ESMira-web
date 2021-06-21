import {Defaults} from "../variables/defaults";
import {OwnMapping} from "./knockout_own_mapping";
import {Site} from "../main_classes/site";
import {safe_confirm} from "./basics";

export function add_default(list, defaultsKey, pageCode) {
	let item = OwnMapping.fromJS(Defaults[defaultsKey], Defaults[defaultsKey]);
	list.push(item);
	
	if(pageCode)
		Site.add_page(pageCode.replace("%", list().length-1));
	return item;
}

export function ko__add_default(key, pageCode) {
	return function(data) {
		add_default(data[key], key, pageCode);
	}
}
export function ko__remove_from_list(list, index, confirm_msg, important) {
	return function() {
		if(confirm_msg && (important &&!safe_confirm(confirm_msg) || (!important &&!confirm(confirm_msg))))
			return false;
		
		list.splice(index, 1);
		return true;
	}
}