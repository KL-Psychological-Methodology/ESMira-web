import html from "./settings.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_ADMIN} from "../js/variables/urls";
import {check_string} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";
import {Site} from "../js/main_classes/site";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Defaults} from "../js/variables/defaults";
import {Studies} from "../js/main_classes/studies";
import {PromiseCache} from "../js/main_classes/promise_cache";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("server_settings"));
	this.promiseBundle = [
		Admin.init(page),
		PromiseCache.loadJson(FILE_ADMIN+"?type=get_serverSettings", function(data) {
			let serverSettings = OwnMapping.fromJS(Defaults.serverSettings, Defaults.serverSettings);
			
			serverSettings.serverName(data.serverName._);
			serverSettings.impressum(data.impressum._);
			serverSettings.privacyPolicy(data.privacyPolicy._);
			
			let langCodes = data.langCodes;
			for(let i=langCodes.length-1; i>=0; --i) {
				let code = langCodes[i];
				let langObj = {};
				langObj.serverName = data.serverName.hasOwnProperty(code) ? data.serverName[code] : '';
				langObj.impressum = data.impressum.hasOwnProperty(code) ? data.impressum[code] : '';
				langObj.privacyPolicy = data.privacyPolicy.hasOwnProperty(code) ? data.privacyPolicy[code] : '';
				
				OwnMapping.add_lang(serverSettings, Defaults.serverSettings, langObj, code);
				serverSettings.langCodes.push(ko.observable(code));
			}
			OwnMapping.unsetDirty(serverSettings);
			
			let detector = Admin.tools.get_changeDetector(serverSettings);
			detector.setDirty(false);
			
			return [serverSettings, detector];
		})
	];
	this.extraContent = "<lang-options params='enableAdd: true, alwaysVisible: true, hasTitle: true, langObj: $root.dataObj, langDefaults: $root.defaults'></lang-options>";
	this.dataObj = OwnMapping.fromJS(Defaults.serverSettings, Defaults.serverSettings);
	this.defaults = Defaults.serverSettings;
	
	this.preInit = function(index, admin, [serverSettings, detector]) {
		this.dataObj = serverSettings;
		Admin.tools.change_observed(
			detector,
			self.change
		);
	};
	this.selectedIndex = ko.observable(0);
	this.serverName = ko.observable();
	this.impressum = ko.observable();
	this.privacyPolicy = ko.observable();
	
	this.destroy = function() {
		Admin.tools.remove_observed();
	}
	
	this.change = function() {
		let getValues = function(code) {
			let s = OwnMapping.toLangJs(self.dataObj, code);
			s.impressum = encodeURIComponent(s.impressum);
			s.privacyPolicy = encodeURIComponent(s.privacyPolicy);
			if(s.serverName.length < 3 || s.serverName.length > 30) {
				page.loader.info(Lang.get("error_short_serverName"));
				return false;
			}
			else if(!check_string(s.serverName)) {
				page.loader.info(Lang.get("error_forbidden_characters"));
				return false;
			}
			return s;
		};

		let s = getValues("_");
		if(!s)
			return;
		let settings = {_: s};

		let langCodes = self.dataObj.langCodes();
		for(let i=langCodes.length-1; i>=0; --i) {
			let code = langCodes[i]();
			let s = getValues(code);
			if(!s)
				return;
			s.lang = code;
			settings[code] = s;
		}

		return page.loader.loadRequest(
			FILE_ADMIN + "?type=save_serverSettings",
			false,
			"post",
			JSON.stringify(settings)
		);
	};
}