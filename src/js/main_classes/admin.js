import {FILE_ADMIN} from "../variables/urls";
import ko from "knockout";
import {PromiseCache} from "./promise_cache";
import {Requests} from "./requests";
import {Studies} from "./studies";

export const Admin = {
	_promiseState: null,
	
	tools: null,
	is_loggedIn: ko.observable(false),
	is_init: true,
	enable_adminFeatures: false, //is set to true in page.js when pageName === "admin" is detected. Mainly used to check if init() is in progress
	
	init: function(page) {
		return PromiseCache.getOrNull("admin") || PromiseCache.save("admin", (Promise.all([
			Requests.load(FILE_ADMIN+"?type=get_permissions"),
			import("../dynamic_imports/tools")
		]).then(function([data, {AdminTools, Studies_tools}]) {
			AdminTools.init(page);
			Studies_tools.init(page);
			self.tools = AdminTools;
			Studies.tools = Studies_tools;
			
			
			// self.is_loggedIn.subscribe(Site.reload_allPages, Site);
			
			if(data["init_esmira"])
				self.is_init = false;
				// page.replace("init_esmira");
			else
				AdminTools.set_loginStatus(data);
			
			
			return self;
		})));
	},
	
	wait_ifNeeded: function(page) {
		if(this.enable_adminFeatures)
			return this.init(page);
		else
			return Promise.resolve(this);
	},
};
let self = Admin;