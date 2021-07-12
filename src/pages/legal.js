import html from "./legal.html"
import {Lang} from "../js/main_classes/lang";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {close_on_clickOutside} from "../js/helpers/basics";
import * as ko from "knockout";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("impressum"));
	this.promiseBundle = [PromiseCache.loadJson("legal.php")];
	this.preInit = function(index, {impressum, privacyPolicy}) {
		if(impressum) {
			if(index.hasOwnProperty("impressum"))
				this.selectedIndex(this.tabs.length);
			this.tabs.push("impressum");
			this.impressum = impressum;
		}
		if(privacyPolicy) {
			if(index.hasOwnProperty("privacyPolicy"))
				this.selectedIndex(this.tabs.length);
			this.tabs.push("privacyPolicy");
			this.privacyPolicy = privacyPolicy;
		}
	}
	
	this.tabs = ['used_libraries'];
	this.showTabs = true;
	this.selectedIndex = ko.observable(0);
	
	this.openPlugins = function(data, e) {
		let target = e.target;
		let dropdown = target.parentNode.querySelector(".dropdown");
		
		dropdown.classList.remove("hidden");
		close_on_clickOutside(dropdown, function() {dropdown.classList.add("hidden");});
	}
}