import {Site} from "../classes/site";
import {FILE_ADMIN} from "../variables/urls";
import ko from "knockout";
import {PromiseCache} from "../classes/promise_cache";

export const Admin = {
	_promiseState: null,
	
	tools: null,
	is_loggedIn: ko.observable(false),
	enable_adminFeatures: false, //is set to true in page.js when pageName === "admin" is detected: mainly used by studies.getUrl()
	
	init: function() {
		return PromiseCache.getOrNull("admin") || PromiseCache.save("admin", (Promise.all([
			Site.load_withState(FILE_ADMIN+"?type=get_permissions"),
			import("../imports/admin_tools"),
			import("../data_loaders/data_loader.js")
		]).then((response) => {
			let data = response[0];
			let tools = response[1].AdminTools;
			Site.dataLoader = response[2].DataLoader;
			
			this.tools = tools;
			tools.is_loggedIn = this.is_loggedIn;
			
			if(data["init_esmira"])
				Site.goto("init_esmira");
			else {
				tools._set_loginStatus(data);
				Site.studies.set_initAgain();
			}
			
			return data["notLoggedIn"];
		})));
		
		
		// return this._promiseState || (this._promiseState = Promise.all([
		// 	Site.load_withState(FILE_ADMIN+"?type=get_permissions"),
		// 	import("../imports/admin_tools"),
		// 	import("data_list")
		// ]).then((response) => {
		// 	let data = response[0];
		// 	let tools = response[1].AdminTools;
		//
		// 	this.tools = tools;
		// 	Site.dataList = response[2];
		//
		// 	if(data["init_esmira"])
		// 		Site.goto("init_esmira");
		// 	else {
		// 		tools._set_loginStatus(data);
		// 		Site.studies.set_initAgain();
		// 	}
		//
		// 	return data["notLoggedIn"];
		// }));
	}
};