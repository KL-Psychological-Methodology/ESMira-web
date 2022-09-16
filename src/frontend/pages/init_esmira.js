import html from "./init_esmira.html"
import {Lang} from "../js/main_classes/lang";
import {Requests} from "../js/main_classes/requests";
import {FILE_ADMIN, FILE_CHECK_HTACCESS} from "../js/variables/urls";
import * as ko from "knockout";
import {Admin} from "../js/main_classes/admin";
import {Site} from "../js/main_classes/site";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("init_esmira"));
	
	this.promiseBundle = Admin.esmira_isInit
		? [Admin.init(page), [], []]
		: [
			Admin.init(page),
			Requests.load(
				FILE_ADMIN + "?type=init_esmira_prep"
			),
			Requests.load(
				FILE_CHECK_HTACCESS,
			)
		];
	
	this.preInit = function(index, admin, {dir_base, dataFolder_exists}, {htaccess, mod_rewrite}) {
		if(admin.esmira_isInit)
			Site.goto("admin");
		
		this.htaccess_enabled = htaccess;
		this.mod_rewrite_enabled = mod_rewrite;
		this.data_location = ko.observable(dir_base);
		this.dataFolder_exists = ko.observable(dataFolder_exists);
		this.reuseFolder = ko.observable(0);
	}
	this.server_name = ko.observable("");
	
	this.create = function(accountName, password) {
		let server_name = self.data_location();
		
		return page.loader.loadRequest(
			FILE_ADMIN + "?type=init_esmira",
			false,
			"post",
			"new_account=" + accountName + "&pass=" + password + "&data_location=" + self.data_location() + "&reuseFolder=" + self.reuseFolder()
		).then(function(data) {
			Admin.esmira_isInit = true;
			Admin.tools.accountName(accountName);
			console.log(Admin);
			Admin.tools.set_loginStatus(data);
		});
	}
	
	this.data_location_changed = function() {
		return page.loader.loadRequest(
			FILE_ADMIN + "?type=data_folder_exists",
			false,
			"post",
			"data_location=" + self.data_location()
		).then(function({dataFolder_exists}) {
			self.dataFolder_exists(dataFolder_exists);
			page.loader.close_loader();
		});
	}
}