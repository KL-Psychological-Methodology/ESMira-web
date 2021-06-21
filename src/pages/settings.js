import html from "./settings.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_ADMIN} from "../js/variables/urls";
import {check_string} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";
import {Site} from "../js/main_classes/site";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("server_settings"));
	this.promiseBundle = [Admin.init(page)];
	
	this.preInit = function() {
		page.loader.loadRequest(FILE_ADMIN+"?type=get_serverSettings").then(function(data) {
			self.original_serverName(data.serverName);
			self.original_impressum(data.impressum);
			self.original_privacyPolicy(data.privacyPolicy);
			self.serverName(data.serverName);
			self.impressum(data.impressum);
			self.privacyPolicy(data.privacyPolicy);
		});
	};
	
	this.selectedIndex = ko.observable(0);
	this.serverName = ko.observable();
	this.impressum = ko.observable();
	this.privacyPolicy = ko.observable();
	
	this.original_serverName = ko.observable();
	this.original_impressum = ko.observable();
	this.original_privacyPolicy = ko.observable();
	
	
	this.change = function() {
		if(self.serverName().length < 3 || self.serverName().length > 30)
			page.loader.info(Lang.get("error_short_serverName"));
		else if(!check_string(self.serverName()))
			page.loader.info(Lang.get("error_forbidden_characters"));
		else {
			page.loader.loadRequest(
				FILE_ADMIN + "?type=save_serverSettings",
				false,
				"post",
				"serverName=" + encodeURIComponent(self.serverName())+"&impressum="+encodeURIComponent(self.impressum())+"&privacyPolicy="+encodeURIComponent(self.privacyPolicy())
			).then(function() {
				document.getElementById("header_serverName").innerText = Site.serverName = self.serverName();
				self.original_serverName(self.serverName());
				self.original_impressum(self.impressum());
				self.original_privacyPolicy(self.privacyPolicy());
				page.loader.info(Lang.get("info_successful"));
			});
		}
	};
}