import html from "./studies.html"
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";
import ko from "knockout";
import {Admin} from "../js/main_classes/admin";
import * as Basics from "../js/helpers/basics";
import message_svg from "../imgs/message.svg?raw";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Studies.init(page), Admin.wait_ifNeeded(page)];
	page.getAlternatives = function() {
		let currentStudy = Studies.get_current();
		if(currentStudy) {
			let id = currentStudy.id();
			return [
				{title: Lang.get('edit_studies'), url: "#admin/studies,edit/studyEdit,id:" + id},
				{title: Lang.get('messages'), url: "#admin/studies,msgs/messages,id:" + id},
				{title: Lang.get('show_data_statistics'), url:  "#admin/studies,data/dataStatistics,id:" + id}
			];
		}
		else {
			return [
				{title: Lang.get('edit_studies'), url: "#admin/studies,edit"},
				{title: Lang.get('messages'), url: "#admin/studies,msgs"},
				{title: Lang.get('show_data_statistics'), url: "#admin/studies,data"}
			];
			
		}
	};
	
	this.check = function() {return true;};
	this.target = function(id) {return id;};
	this.selectedIndex = ko.observable(1);
	this.useAccessKeys = true;
	this.showFilter = false;
	// this.has_messages = Admin.is_loggedIn() ? Studies.tools.newMessages().count : 0;
	this.currentAccessKeyIndex = ko.observable(-1);
	this.tabs = ['public_studies', 'hidden_studies', 'disabled'];
	
	
	
	this.preInit = function() {
		// if(!Admin.is_loggedIn() && !studies.length && Studies.accessKey().length) {
		// 	page.loader.info(Lang.get("error_wrong_accessKey"));
		// 	// throw new Error(Lang.get("error_wrong_accessKey"));
		// }
		if(Admin.is_loggedIn() && Studies.tools.newMessages().count()) {
			page.hasAlternatives(true);
			this.has_messages = true;
			this.tabs.unshift("messages");
			this.selectedIndex(2);
		}
		else
			this.has_messages = false;
		
		if(page.index.hasOwnProperty("attend")) {
			this.target = function(id) {return "sOverview,id:"+id};
			page.title(Lang.get("select_a_study"));
		}
		else if(page.index.hasOwnProperty("appInstall")) {
			this.target = function(id) {return "appInstall,id:"+id};
			page.title(Lang.get("select_a_study"));
		}
		else if(page.index.hasOwnProperty("participant")) {
			this.target = function(id) {return "participant,id:"+id};
			page.title(Lang.get("select_a_study"));
		}
		else if(page.index.hasOwnProperty("consent")) {
			this.target = function(id) {return "consent,id:"+id};
			page.title(Lang.get("select_a_study"));
		}
		else if(page.index.hasOwnProperty("data")) {
			this.useAccessKeys = false;
			this.showFilter = Admin.is_loggedIn();
			this.check = function(study) {return study.version() !== 0;};
			this.target = function(id) {return "dataStatistics,id:"+id};
			page.title(Lang.get("data"));
		}
		else if(page.index.hasOwnProperty("edit")) {
			this.useAccessKeys = false;
			this.showFilter = Admin.is_loggedIn();
			this.target = function(id) {return "studyEdit,id:"+id};
			page.title(Lang.get("edit_studies"));
		}
		else if(page.index.hasOwnProperty("msgs")) {
			this.useAccessKeys = false;
			this.showFilter = Admin.is_loggedIn();
			if(Admin.is_loggedIn() && Studies.tools.newMessages().count())
				this.selectedIndex(0);
			this.check = function(study) {return study.version() !== 0};
			this.target = function(id) {return "messages,id:"+id};
			page.title(Lang.get("messages"));
		}
		else if(page.index.hasOwnProperty("statistics")) {
			this.check = function(study) {return study.publicStatistics.charts().length;};
			this.target = function(id) {return "statistics,id:"+id};
			page.title(Lang.get("statistics"));
		}
		else {
			this.target = function(id) {return "sOverview,id:"+id};
			page.title(Lang.get("select_a_study"));
		}
	}
	
	this.get_tabName = function(tabName) {
		return (tabName === "messages") ? message_svg : Lang.get(tabName);
		
	}
	
	this.is_selected = function(study) {
		let selectedAccessKey = self.currentAccessKeyIndex() !== -1 ? Studies.all_accessKeys()[self.currentAccessKeyIndex()] : null;
		switch(self.tabs[self.selectedIndex()]) {
			case 'public_studies':
				return study.published() && !study.accessKeys().length;
			case 'hidden_studies':
				if(selectedAccessKey !== null && study.accessKeys.indexOf(selectedAccessKey) === -1)
					return false;
				return study.published() && study.accessKeys().length;
			case 'disabled':
				return !study.published();
			case 'messages':
				return Studies.tools.newMessages()[study.id()];
			default:
				return !(selectedAccessKey !== null && study.accessKeys.indexOf(selectedAccessKey) === -1);
		}
	}
	
	this.change_accessKey = function() {
		let accessKey = document.getElementById("accessKey_el").value;
		Basics.save_cookie("access_key", accessKey);
		Studies.accessKey(accessKey);
		Studies.reload(page);
	};
}