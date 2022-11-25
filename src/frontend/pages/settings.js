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
import {addLang, changeLang} from "../js/shared/lang_configs";
import {DetectChange} from "../js/main_classes/detect_change";
import {NavigationRow} from "../js/main_classes/navigation_row";
import {Requests} from "../js/main_classes/requests";

let defaultServerSettings = {
	langCodes: ["en"],
	defaultLang: "en",
	$: {
		translated: {
			impressum: "",
			serverName: "",
			privacyPolicy: "",
		}
	}
}

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("server_settings"));
	this.promiseBundle = [
		Admin.init(page),
		PromiseCache.loadJson(FILE_ADMIN+"?type=get_serverConfigs", function({configs, translationData}) {
			let serverSettings = OwnMapping.fromJS(configs, defaultServerSettings);
			let detector = new DetectChange(serverSettings);
			let translations = {__detector: detector};
			
			for(let langCode in translationData) {
				let translationsSet = translationData[langCode];
				for(let key in translationsSet) {
					configs[key] = translationsSet[key];
				}
				
				let lang = OwnMapping.bindNewLanguageContainer(serverSettings, configs);
				translations[langCode] = lang;
				detector.addMonitored(lang);
			}
			
			// detector.addMonitored(translations);
			return [serverSettings, translations, detector];
		})
	];
	this.extraContent = "<lang-chooser params='hasTitle: true, langObj: $root.dataObj, translations: $root.translations'></lang-chooser>";
	this.dataObj = null;
	
	this.isPreRelease = ko.observable(0);
	this.releases = ko.observableArray();
	this.downloadUrl = null
	this.markdownRenderer = new MarkdownIt();
	
	let versionIsBelowThen =  function(newVersionString) {
		let integersOld = PACKAGE_VERSION.match(/(\d+)\.(\d+)\.(\d+)\D*(\d*)/);
		let integersNew = newVersionString.match(/(\d+)\.(\d+)\.(\d+)\D*(\d*)/);
		
		return integersOld && integersNew &&
			(
				integersNew[1] > integersOld[1] // e.g. 2.0.0 > 1.0.0
				|| (
					integersNew[1] === integersOld[1]
					&& (
						integersNew[2] > integersOld[2] // e.g. 2.1.0 > 2.0.0
						|| (
							integersNew[2] === integersOld[2]
							&& (
								integersNew[3] > integersOld[3] // e.g. 2.1.1 > 2.1.0
								|| (
									integersNew[3] === integersOld[3]
									&& (
										(integersOld[4] !== '' && integersNew[4] === '') // e.g. 2.1.1 > 2.1.1-alpha.1
										|| (integersOld[4] !== '' && integersNew[4] !== '' && integersNew[4] > integersOld[4]) // e.g. 2.1.1-alpha.2 > 2.1.1-alpha.1
									)
								)
							)
						)
					)
				)
			);
	}
	
	let checkForUpdate = function() {
		page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadText(URL_RELEASES_LIST, function(jsonString) {
			let releases = JSON.parse(jsonString);
			let stableReleases = [];
			let unstableReleases = [];
			
			let searchingForRelease = true;
			for(let i=releases.length-1; i>=0; --i) {
				let {tag_name, prerelease, body, published_at, assets} = releases[i];
				if(!versionIsBelowThen(tag_name))
					continue
				// if(searchingForRelease) {
				// 	if(tag_name.match(PACKAGE_VERSION+"$"))
				// 		searchingForRelease = false;
				// 	continue;
				// }
				
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
	
	this.preInit = function(index, admin, [serverSettings, translations, detector]) {
		// changeLang(serverSettings, translations, serverSettings.defaultLang());
		self.translations = translations;
		self.dataObj = serverSettings;
		NavigationRow.admin.change_observed(
			detector,
			self.saveServerSettings
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
	
	
	this.translations = null;
	
	this.hasUpdate = ko.observable(false);
	this.noConnectionToUpdate = ko.observable(false);
	this.newVersion = ko.observable("");
	
	
	this.remove_lang = function(code) {
		let index = self.dataObj.langCodes.indexOf(code);
		self.dataObj.langCodes.splice(index, 1);
	}
	
	this.saveServerSettings = function() {
		let translationData = {};
		let langCodes = self.dataObj.langCodes();
		for(let i=langCodes.length-1; i>=0; --i) {
			let code = langCodes[i]();
			changeLang(self.dataObj, self.translations, code);
			translationData[code] = {
				serverName: self.dataObj.serverName(),
				impressum: self.dataObj.impressum(),
				privacyPolicy: self.dataObj.privacyPolicy(),
			};
		}
		console.log({
			configs: {
				langCodes: OwnMapping.toJS(langCodes),
				defaultLang: self.dataObj.defaultLang()
			},
			translationData: translationData
		})

		return page.loader.loadRequest(
			FILE_ADMIN + "?type=save_serverConfigs",
			false,
			"post",
			JSON.stringify({
				configs: {
					langCodes: OwnMapping.toJS(langCodes),
					defaultLang: self.dataObj.defaultLang()
				},
				translationData: translationData
			})
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
					page.loader.update(Lang.get("state_finish_installing"))
					return Requests.load(FILE_ADMIN + "?type=update_version&fromVersion="+PACKAGE_VERSION);
				})
				.then(function() {
					alert(Lang.get("info_web_update_complete"));
					return window.location.reload();
				})
		)
	}
}