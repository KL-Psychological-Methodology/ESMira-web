import html from "./input.html"
import {Studies} from "../js/main_classes/studies";
import {get_uniqueName} from "../js/shared/inputs";
import {Admin} from "../js/main_classes/admin";
import {Defaults} from "../js/variables/defaults";
import {Lang} from "../js/main_classes/lang";
import {selectedQuestionnaire} from "../js/shared/questionnaire_edit";
import * as ko from "knockout";

export function ViewModel(pageModel) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Studies.init(pageModel)];
	
	this.preInit = function({id, q, page, input}, studies) {
		this.for_subItem = pageModel.index.hasOwnProperty("sub");
		let questionnaire = studies[id].questionnaires()[q];
		
		if(this.for_subItem)
			this.dataObj = questionnaire.pages()[page].inputs()[input].subInputs()[pageModel.index.sub];
		else
			this.dataObj = questionnaire.pages()[page].inputs()[input];
		
		
		pageModel.title(ko.pureComputed(function() {
			return self.dataObj.name() + " (" + questionnaire.title() + ")";
		}));
		
		this.isCurrent = ko.pureComputed(function() {
			return parseInt(q) === selectedQuestionnaire();
		});
	};
	
	let listTools = Admin.tools.get_listTools(pageModel);
	this.add_subInput = function(input) {
		let subInput = listTools.add_obj(input.subInputs, Defaults.inputs, "input,sub:%");
		subInput.name(input.name());
	}
	
	this.add_listChoice = function(input) {
		listTools.add_prompted(input.listChoices);
	}
	
	this.optionsInput = [
		{key: "text", value: Lang.get("input_text")},
		{key: "video", value: Lang.get("input_video")},
		{key: "image", value: Lang.get("input_image")},
		{key: "text_input", value: Lang.get("input_text_input")},
		{key: "number", value: Lang.get("input_number")},
		{key: "binary", value: Lang.get("input_binary")},
		{key: "date", value: Lang.get("input_date")},
		{key: "date_old", value:  "Alte Datumseingabe (nicht verwenden!!!)"}, //TODO: Kann nach Selinas Studie entfernt werden
		{key: "time_old", value: "Alte Zeiteingabe (nicht verwenden!!!)"}, //TODO: Kann nach Selinas Studie entfernt werden
		{key: "time", value: Lang.get("input_time")},
		{key: "likert", value: Lang.get("input_likert")},
		{key: "va_scale", value: Lang.get("input_va_scale")},
		{key: "list_single", value: Lang.get("input_list_single")},
		{key: "list_multiple", value: Lang.get("input_list_multiple")},
		{key: "dynamic_input", value: Lang.get("input_dynamic_input")},
		{key: "app_usage", value: Lang.get("input_app_usage")}
	];
	this.optionsSubInput = [
		{key: "text", value: Lang.get("input_text")},
		{key: "image", value: Lang.get("input_image")},
		{key: "video", value: Lang.get("input_video")},
		{key: "text_input", value: Lang.get("input_text_input")},
		{key: "number", value: Lang.get("input_number")},
		{key: "binary", value: Lang.get("input_binary")},
		{key: "date", value: Lang.get("input_date")},
		{key: "time", value: Lang.get("input_time")},
		{key: "likert", value: Lang.get("input_likert")},
		{key: "va_scale", value: Lang.get("input_va_scale")},
		{key: "list_multiple", value: Lang.get("input_list_multiple")},
		{key: "list_single", value: Lang.get("input_list_single")}
	];
	this.get_uniqueName = get_uniqueName;
	
	this.ko__remove_from_list = listTools.ko__remove_from_list;
}