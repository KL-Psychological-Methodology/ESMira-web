import html from "./reward_code.html"
import {Site} from "../js/main_classes/site";
import {Studies} from "../js/main_classes/studies";
import ko from "knockout";
import {FILE_ADMIN} from "../js/variables/urls";
import {Lang} from "../js/main_classes/lang";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	page.title(Lang.get("validate_reward_code"));
	
	this.code = ko.observable("");
	this.timestamp = ko.observable();
	this.showResponse = ko.observable(false);
	this.faultyCode = ko.observable(false);
	this.questionnaireEntryCount = ko.observableArray();
	
	this.checkCode = function() {
		self.showResponse(false);
		let study_id = Site.valueIndex.id;
		
		page.loader.loadRequest(
			FILE_ADMIN+"?type=validate_reward_code",
			false,
			"post",
			"study_id="+study_id+"&code="+self.code()
		).then(function({faultyCode, timestamp, questionnaireDataSetCount}) {
			if(faultyCode) {
				self.faultyCode(true);
				self.showResponse(true);
				return;
			}
			else
				self.faultyCode(false);
			
			self.timestamp(timestamp);
			
			
			//create internalId index:
			let questionnaireIndex = {};
			let study = Studies.get_current();
			let questionnaires = study.questionnaires();
			for(let i = questionnaires.length - 1; i >= 0; --i) {
				let questionnaire = questionnaires[i];
				questionnaireIndex[questionnaire.internalId()] = questionnaire.title();
			}
			
			
			//combine data:
			let questionnaireEntryCount = [];
			for(let internalId in questionnaireDataSetCount) {
				if(!questionnaireDataSetCount.hasOwnProperty(internalId))
					continue;
				questionnaireEntryCount.push({title: questionnaireIndex[internalId], count: questionnaireDataSetCount[internalId]});
			}
			console.log(questionnaireEntryCount);
			self.questionnaireEntryCount(questionnaireEntryCount);
			
			self.showResponse(true);
		});
	};
}