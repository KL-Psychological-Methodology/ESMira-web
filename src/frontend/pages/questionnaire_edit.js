import html from "./questionnaire_edit.html"
import {Studies} from "../js/main_classes/studies";
import {get_uniqueName} from "../js/shared/inputs";
import {Admin} from "../js/main_classes/admin";
import {Defaults} from "../js/variables/defaults";
import {Lang} from "../js/main_classes/lang";
import {selectedQuestionnaire} from "../js/shared/questionnaire_edit";
import {Site} from "../js/main_classes/site";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("questionnaires"));
	this.promiseBundle = [Studies.init(page), Site.init_drag()];
	this.extraContent = "<div data-bind=\"if: $root.selectedIndex() !== undefined\">" +
		"<a class=\"right\" data-bind=\"navi: 'attend,demo,q:'+$root.selectedIndex(), text: Lang.get('preview')\"></a>" +
		"</div>";
	
	this.selectedIndex = selectedQuestionnaire;
	
	this.preInit = function({id, q}, studies) {
		this.study = studies[id];
		this.selectedIndex(q ? parseInt(q) : 0);
	};
	
	let listTools = Admin.tools.get_listTools(page);
	this.add_input = function(pageIndex, questionnaireIndex, selectedPage) {
		let name = get_uniqueName();
		if(!name)
			return;
		
		let input = listTools.add_obj(selectedPage.inputs, Defaults.inputs, "input:%,pageI:"+pageIndex+",q:"+questionnaireIndex);
		input.name(name);
	};
	this.add_questionnaire = function() {
		Studies.tools.add_questionnaire(page, self.study).then(function() {
			self.selectedIndex(self.study.questionnaires().length-1);
		});
	}
	this.delete_questionnaire = function(index, questionnaire) {
		let koQuestionnaires = self.study.questionnaires;
		let internalId = questionnaire.internalId();
		let listTools = Admin.tools.get_listTools(page);
		if(!listTools.remove_from_list(koQuestionnaires, index, Lang.get("confirm_delete_questionnaire", questionnaire.title()), true))
			return;
		
		//remove specificGroupInternalId in eventTriggers:
		let questionnaires = koQuestionnaires();
		for(let qI=questionnaires.length-1; qI>=0; --qI) {
			let triggers = questionnaires[qI].actionTriggers();
			for(let triggerI=triggers.length-1; triggerI>=0; --triggerI) {
				let eventTriggers = triggers[triggerI].eventTriggers();
				for(let cueI=eventTriggers.length-1; cueI>=0; --cueI) {
					let cue = eventTriggers[cueI];
					if(cue.specificQuestionnaireInternalId() === internalId) {
						cue.specificQuestionnaireEnabled(false);
						cue.specificQuestionnaireInternalId(-1);
					}
				}
			}
		}
	}
	
	let movePage = function(qIndex, pIndex, direction, scrollDir) {
		let ko_questionnaires = self.study.questionnaires()[qIndex].pages;
		let questionnaires = ko_questionnaires()
		let temp = questionnaires[pIndex];
		questionnaires[pIndex] = questionnaires[pIndex + direction];
		questionnaires[pIndex + direction] = temp;
		ko_questionnaires.valueHasMutated();
		
		window.setTimeout(function() {
			let parentBox = document.getElementById("questionnaire_editBox");
			parentBox.children[pIndex + direction].scrollIntoView({behavior: "smooth", block: "start"});
		}, 100);
	}
	this.movePageUp = function({qIndex, pIndex}) {
		movePage(qIndex, pIndex, -1);
		
	}
	this.movePageDown = function({qIndex, pIndex}) {
		movePage(qIndex, pIndex, +1);
	}
	
	this.ko__add_default = listTools.ko__add_default;
	this.ko__remove_from_list = listTools.ko__remove_from_list;
}