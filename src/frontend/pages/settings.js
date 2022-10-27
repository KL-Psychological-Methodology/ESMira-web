import html from "./settings.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import MarkdownIt from "markdown-it";
import {FILE_ADMIN, URL_RELEASES_LIST} from "../js/variables/urls";
import {check_string} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Defaults} from "../js/variables/defaults";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {add_lang} from "../js/shared/lang_configs";
import {DetectChange} from "../js/main_classes/detect_change";
import {NavigationRow} from "../js/main_classes/navigation_row";
import {Requests} from "../js/main_classes/requests";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("server_settings"));
	this.promiseBundle = [
		Admin.init(page),
		PromiseCache.loadJson(FILE_ADMIN+"?type=get_serverConfigs", function(data) {
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
			
			let detector = new DetectChange(serverSettings);
			detector.setDirty(false);
			
			return [serverSettings, detector];
		})
	];
	this.extraContent = "<lang-options params='enableAdd: true, alwaysVisible: true, hasTitle: true, langObj: $root.dataObj, langDefaults: $root.defaults'></lang-options>";
	this.dataObj = OwnMapping.fromJS(Defaults.serverSettings, Defaults.serverSettings);
	this.defaults = Defaults.serverSettings;
	
	this.isPreRelease = ko.observable(0);
	this.releases = ko.observableArray();
	this.downloadUrl = null
	this.markdownRenderer = new MarkdownIt();
	
	let checkForUpdate = function() {
		page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadText(URL_RELEASES_LIST, function(jsonString) {
			let releases = JSON.parse(jsonString);
			let stableReleases = [];
			let unstableReleases = [];
			
			let searchingForRelease = true;
			for(let i=releases.length-1; i>=0; --i) {
				let {tag_name, prerelease, body, published_at, assets} = releases[i];
				if(searchingForRelease) {
					if(tag_name.match(PACKAGE_VERSION+"$"))
						searchingForRelease = false;
					continue;
				}
				
				let data = {version: tag_name, date: new Date(published_at), changeLog: body, downloadUrl: assets[0].browser_download_url};
				if(prerelease)
					unstableReleases.push(data);
				else
					stableReleases.push(data);
			}
			return [stableReleases, unstableReleases];
		}).then(function([stableReleases, unstableReleases]) {
			let releases = self.isPreRelease() ? unstableReleases : stableReleases;
			self.releases(releases);
			self.releases.valueHasMutated();
			
			self.hasUpdate(!!releases.length);
			if(releases.length) {
				let release = releases[releases.length-1];
				self.newVersion(release.version);
				self.downloadUrl = release.downloadUrl;
			}
		}));
	};
	
	this.isPreRelease.subscribe(checkForUpdate);
	
	this.preInit = function(index, admin, [serverSettings, detector]) {
		this.dataObj = serverSettings;
		this.add_lang = add_lang.bind(this, serverSettings, Defaults.serverSettings);
		NavigationRow.admin.change_observed(
			detector,
			self.changeText
		);
	};
	this.postInit = checkForUpdate;
	this.destroy = function() {
		NavigationRow.admin.remove_observed();
	}
	this.selectedIndex = ko.observable(0);
	this.serverName = ko.observable();
	this.impressum = ko.observable();
	this.privacyPolicy = ko.observable();
	this.currentVersion = PACKAGE_VERSION;
	
	this.hasUpdate = ko.observable(false);
	this.noConnectionToUpdate = ko.observable(false);
	this.newVersion = ko.observable("");
	this.changelog = ko.observable("");
	
	
	this.remove_lang = function(code) {
		let index = self.dataObj.langCodes.indexOf(code);
		self.dataObj.langCodes.splice(index, 1);
	}
	
	this.changeText = function() {
		let getValues = function(code) {
			let obj = OwnMapping.toLangJs(self.dataObj, code);
			if(obj.impressum)
				obj.impressum = encodeURIComponent(obj.impressum);
			if(obj.privacyPolicy)
				obj.privacyPolicy = encodeURIComponent(obj.privacyPolicy);
			
			if(!obj.serverName || obj.serverName.length < 3 || obj.serverName.length > 30) {
				page.loader.info(Lang.get("error_short_serverName", "en"));
				return false;
			} else if(!check_string(obj.serverName)) {
				page.loader.info(Lang.get("error_forbidden_characters"));
				return false;
			}
			return obj;
		};

		let s = getValues("_");
		if(!s)
			return;
		let settings = {_: s};

		let langCodes = self.dataObj.langCodes();
		for(let i=langCodes.length-1; i>=0; --i) {
			let code = langCodes[i]();
			let currentObj = getValues(code);
			if(!currentObj)
				return;
			currentObj.lang = code;
			settings[code] = currentObj;
		}

		return page.loader.loadRequest(
			FILE_ADMIN + "?type=save_serverConfigs",
			false,
			"post",
			JSON.stringify(settings)
		);
	};
	
	this.switchBranch = checkForUpdate;
	
	this.updateNow = function() {
		if(self.isPreRelease()) {
			if(!confirm(Lang.get("confirm_prerelease_update")))
				return;
		}
		page.loader.showLoader(
			Lang.get("state_downloading"),
			Requests.load(
				FILE_ADMIN + "?type=download_update",
				false,
				"post",
				"url="+self.downloadUrl
			)
				.then(function() {
					page.loader.update(Lang.get("state_installing"))
					return Requests.load(FILE_ADMIN + "?type=do_update");
				})
				.then(function() {
					page.loader.update(Lang.get("state_installing"))
					return Requests.load(FILE_ADMIN + "?type=update_version&fromVersion="+PACKAGE_VERSION);
				})
				.then(function() {
					alert(Lang.get("info_web_update_complete"));
					return window.location.reload();
				})
		)
	}
}