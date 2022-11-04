import html from "./study_edit.html"
import {Studies} from "../js/main_classes/studies";
import * as ko from "knockout";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	this.study = null;
	this.extraContent = "<lang-chooser params='enableAdd: true, alwaysVisible: true, hasTitle: true'></lang-chooser>";
	
	this.preInit = function({id}, studies) {
		let study = studies[id];
		this.study = study;
		page.title(ko.computed(function() {return study.title();}));
	};
	
	this.add_questionnaire = function() {
		Studies.tools.add_questionnaire(page, self.study, "qEdit,q:%");
	};
}