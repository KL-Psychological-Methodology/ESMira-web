import html from "./input.html"
import {Studies} from "../js/main_classes/studies";
import {get_uniqueName} from "../js/shared/inputs";
import {Admin} from "../js/main_classes/admin";
import {Defaults} from "../js/variables/defaults";
import {Lang} from "../js/main_classes/lang";
import {selectedQuestionnaire} from "../js/shared/questionnaire_edit";
import * as ko from "knockout";
import {Site} from "../js/main_classes/site";

export function ViewModel(pageModel) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Studies.init(pageModel), Site.init_drag()];
	
	this.preInit = function({id, q, pageI, input}, studies) {
		this.for_subItem = pageModel.index.hasOwnProperty("sub");
		let questionnaire = studies[id].questionnaires()[q];
		
		if(this.for_subItem)
			this.dataObj = questionnaire.pages()[pageI].inputs()[input].subInputs()[pageModel.index.sub];
		else
			this.dataObj = questionnaire.pages()[pageI].inputs()[input];
		
		
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
	
	this.optionsSubInput = [
		{key: "app_usage", value: Lang.get("input_app_usage")},
		{key: "text", value: Lang.get("input_text")},
		{key: "image", value: Lang.get("input_image")},
		{key: "video", value: Lang.get("input_video")},
		{key: "photo", value: Lang.get("input_photo")},
		{key: "file_upload", value: Lang.get("input_file_upload")},
		{key: "record_audio", value: Lang.get("input_record_audio")},
		{key: "text_input", value: Lang.get("input_text_input")},
		{key: "number", value: Lang.get("input_number")},
		{key: "binary", value: Lang.get("input_binary")},
		{key: "compass", value: Lang.get("input_compass")},
		{key: "countdown", value: Lang.get("input_countdown")},
		{key: "date", value: Lang.get("input_date")},
		{key: "share", value: Lang.get("input_share")},
		{key: "time", value: Lang.get("input_time")},
		{key: "likert", value: Lang.get("input_likert")},
		{key: "va_scale", value: Lang.get("input_va_scale")},
		{key: "list_multiple", value: Lang.get("input_list_multiple")},
		{key: "list_single", value: Lang.get("input_list_single")}
	];
	this.optionsInput = this.optionsSubInput.concat({key: "dynamic_input", value: Lang.get("input_dynamic_input")});
	
	this.get_uniqueName = get_uniqueName;
	
	this.ko__remove_from_list = listTools.ko__remove_from_list;
}