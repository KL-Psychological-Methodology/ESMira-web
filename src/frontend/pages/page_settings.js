import html from "./page_settings.html"
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";
import {selectedQuestionnaire} from "../js/shared/questionnaire_edit";
import * as ko from "knockout";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function({id, pageI}, studies) {
		page.title(Lang.get("edit_page_x", parseInt(pageI) + 1));
		this.dataObj = ko.pureComputed(function() {
			return studies[id].questionnaires()[selectedQuestionnaire()].pages()[pageI];
		});
	};
}