import html from "./sumscores.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import {get_uniqueName} from "../js/shared/inputs";
import {Admin} from "../js/main_classes/admin";
import {Defaults} from "../js/variables/defaults";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	page.title(Lang.get("edit_sumScores"));
	
	this.dataObj = null;
	
	this.preInit = function({id}, studies) {
		this.dataObj = studies[id];
	};
	
	let listTools = Admin.tools.get_listTools(page);
	this.add_sumScore = function(questionnaireIndex, questionnaire) {
		let name = get_uniqueName();
		if(!name)
			return;
		
		let input = listTools.add_obj(questionnaire.sumScores, Defaults.sumScores, "sumScore:%,q:"+questionnaireIndex);
		input.name(name);
	};
	
	this.ko__remove_from_list = listTools.ko__remove_from_list;
}