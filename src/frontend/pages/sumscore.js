import html from "./sumscore.html"
import ko from "knockout"
import {Studies} from "../js/main_classes/studies";
import {get_uniqueName} from "../js/shared/inputs";
import {Admin} from "../js/main_classes/admin";
import {create_axisValues} from "../js/shared/charts";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	
	this.dataObj = null;
	this.questionnaire = null;
	
	this.preInit = function({id, q, sumScore}, studies) {
		this.questionnaire = studies[id].questionnaires()[q];
		this.dataObj = this.questionnaire.sumScores()[sumScore];
		page.title(this.dataObj.name);
		
		this.axisValues = create_axisValues(this.questionnaire);
	};
	
	let listTools = Admin.tools.get_listTools(page);
	
	this.change_sumScore = function(sumScore) {
		sumScore.name(get_uniqueName(sumScore.name()));
	};
	
	this.add_addition = function(sumScore, e) {
		console.log(sumScore);
		let el = e.target;
		sumScore.addList.push(ko.observable(el.value));
		el.selectedIndex = 0;
	};
	this.add_subtraction = function(sumScore, e) {
		let el = e.target;
		sumScore.subtractList.push(ko.observable(el.value));
		el.selectedIndex = 0;
	};
	
	this.ko__remove_from_list = listTools.ko__remove_from_list;
}