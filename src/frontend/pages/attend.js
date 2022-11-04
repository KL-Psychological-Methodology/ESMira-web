import html from "./attend.html"
import {Lang} from "../js/main_classes/lang";
import {Site} from "../js/main_classes/site";
import {delete_cookie, get_cookie, save_cookie} from "../js/helpers/basics";
import ko from "knockout";
import {COOKIE_PARTICIPANT} from "./participant";
import {Studies} from "../js/main_classes/studies";
import {participant_isValid} from "../js/shared/participant";
import binary from "../inputs/binary.html";
import date from "../inputs/date.html";
import dynamic_input from "../inputs/dynamic_input.html";
import image from "../inputs/image.html";
import likert from "../inputs/likert.html";
import list_multiple from "../inputs/list_multiple.html";
import list_single from "../inputs/list_single.html";
import number from "../inputs/number.html";
import text from "../inputs/text.html";
import text_input from "../inputs/text_input.html";
import time from "../inputs/time.html";
import va_scale from "../inputs/va_scale.html";
import video from "../inputs/video.html";
import error from "../inputs/error.html";


const COOKIE_LAST_COMPLETED = "last_completed%1_%2";
const COOKIE_CURRENT_PAGE = "current_page%1_%2";
const COOKIE_RESPONSES_CACHE = "responses_cache%1_%2";


export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	
	this.current_page = ko.observable(0);
	this.participant_id = ko.observable();
	this.responses_cache = {};
	this.formStarted = 0;
	this.pageType = Site.valueIndex.hasOwnProperty("demo") ? "demo" : "questionnaire";
	this.viewModel_pages = [];
	this.wasFinished = ko.observable(false);
	
	
	let viewModel_pages, study, questionnaire;
	
	this.preInit = function({id, q, qId}, studies) {
		//get essentials:
		if(!id) {
			let [foundStudy, foundQuestionnaire] = Studies.get_studyByInternalId(qId);
			study = foundStudy;
			questionnaire = foundQuestionnaire;
			Studies.set_current(study);
		}
		else {
			study = studies[id];
			if(q)
				questionnaire = study.questionnaires()[q];
			else
				questionnaire = Studies.get_questionnaireByInternalId(study, qId);
		}
		let study_id = study.id();
		
		if(!questionnaire) {
			this.dataObj = null;
			page.replace("sOverview");
			return;
		}
		
		
		//redirect if needed:
		if(this.pageType !== "demo") {
			let participant_id = get_cookie(COOKIE_PARTICIPANT.replace("%d", study_id));
			if(!participant_id || !participant_isValid(participant_id)) {
				this.dataObj = null;
				page.replace("participant");
				return;
			}
			this.participant_id(participant_id);
			
			if(!get_cookie("informed_consent" + study_id)) {
				this.dataObj = null;
				page.replace("consent");
				return;
			}
			
			if(!study.publishedWeb()) {
				this.dataObj = null;
				page.replace("appInstall");
				return;
			}
		}
		
		
		//load data from cache:
		let cacheCookie = get_cookie(COOKIE_RESPONSES_CACHE.replace("%1", study_id).replace("%2", questionnaire.internalId()));
		try {
			this.responses_cache = cacheCookie ? JSON.parse(cacheCookie) : {};
			if(typeof this.responses_cache !== 'object' || this.responses_cache === null)
				this.responses_cache = {};
		}
		catch(e) {
			console.warn(e);
			this.responses_cache = {};
		}
		let pageCookie = get_cookie(COOKIE_CURRENT_PAGE.replace("%1", study_id).replace("%2", questionnaire.internalId()));
		this.current_page(pageCookie ? parseInt(pageCookie) : 0);
		
		
		//create questionnaire viewModel:
		viewModel_pages = ko.computed(function() {
			let qPages = questionnaire.pages();
			let responses_cache = self.responses_cache;
			let viewModel_pages = [];
			for(let i=0, max=qPages.length; i<max; ++i) {
				viewModel_pages.push(new Questionnaire_viewModel(page, study, questionnaire, qPages[i], responses_cache));
			}
			return viewModel_pages;
		});
		
		
		//show title:
		let changeTitle = function() {
			page.title( questionnaire.title() + (questionnaire.pages().length > 1 ? ' ('+(self.current_page()+1)+' / '+questionnaire.pages().length+')' : ''));
		};
		this.current_page.subscribe(changeTitle);
		changeTitle();
		
		
		//define variables:
		this.dataObj = questionnaire;
		this.study = study;
		this.formStarted = Date.now();
		this.qPage = ko.computed(function() {return questionnaire.pages()[self.current_page()];});
		this.viewModel_page = ko.computed(function() {return viewModel_pages()[self.current_page()];});
		this.active = ko.computed(function() {return self.pageType === 'demo' || (Studies.questionnaire_isActive(questionnaire) && !self.wasFinished())});
		if(study.contactEmail)
			this.extraContent = "<a class=\"small_text\" href=\""+study.contactEmail()+"\">"+Lang.get('contactEmail')+"</a>";
		
		
		//save access:
		if(!page.depth)
			Site.save_access(page, study.id(), "questionnaire " + questionnaire.internalId());
	};
	
	this.edit_participant = function() {
		let study_id = study.id();
		let new_participant = self.participant_id();
		let old_participant = new_participant;
		
		do {
			new_participant = prompt(Lang.get("error_participant_wrong_format"), new_participant);
			if(new_participant === null)
				return;
		} while(!participant_isValid(new_participant));
		
		save_cookie(COOKIE_PARTICIPANT.replace("%d", study_id), new_participant);
		self.participant_id(new_participant);
		
		Site.save_dataset(page, "quit", old_participant).then(function() {
			Site.save_dataset(page, "joined", new_participant)
		});
	}
	
	this.scrollUp = function() {
		window.setTimeout(function() {
			self.viewModel_page().inputs[0].currentElement.scrollIntoView({behavior: 'smooth'});
		}, 0);
	};
	this.next_page = function() {
		let study_id = study.id();
		
		if(!self.viewModel_page().has_missings()) {
			let pageNum = self.current_page();
			self.current_page(pageNum+1);
			save_cookie(COOKIE_CURRENT_PAGE.replace("%1", study_id).replace("%2", questionnaire.internalId()), pageNum+1);
			self.scrollUp();
		}
	};
	this.prev_page = function() {
		let study_id = study.id();
		let pageNum = self.current_page() - 1;
		self.current_page(pageNum);
		save_cookie(COOKIE_CURRENT_PAGE.replace("%1", study_id).replace("%2", questionnaire.internalId()), pageNum);
		self.scrollUp();
	};
	
	this.save_responses = function() {
		let pageNum = self.current_page();
		if(viewModel_pages()[pageNum].has_missings())
			return;
		
		let study_id = study.id();
		
		let participant_id = self.participant_id();
		
		if(!participant_id)
			page.loader.error(Lang.get("error_unknown"));
		
		if(questionnaire.sumScores) {
			let sumScores = questionnaire.sumScores();
			for(let i=sumScores.length-1; i>=0; --i) {
				let score = sumScores[i];
				let sum = 0;
				let addList = score.addList();
				let subtractList = score.subtractList();
				
				for(let addList_i=addList.length-1; addList_i>=0; --addList_i) {
					let key = addList[addList_i]();
					if(self.responses_cache.hasOwnProperty(key))
						sum += parseInt(self.responses_cache[key]) || 0;
				}
				for(let subtractList_i=subtractList.length-1; subtractList_i>=0; --subtractList_i) {
					let key = subtractList[subtractList_i]();
					if(self.responses_cache.hasOwnProperty(key))
						sum -= parseInt(self.responses_cache[key]) || 0;
				}
				self.responses_cache[score.name()] = sum;
			}
		}
		
		self.responses_cache.formDuration = Date.now() - self.formStarted;
		
		Site.save_dataset(page, "questionnaire", participant_id, questionnaire, self.responses_cache).then(function({states}) {
			let data_answer = states[0];
			if(data_answer) {
				if(!data_answer.hasOwnProperty("success") || !data_answer.success) {
					page.loader.error(data_answer.hasOwnProperty("error") ? data_answer.error : Lang.get("error_unknown"));
					return;
				}
				self.wasFinished(true);
				
				save_cookie(COOKIE_CURRENT_PAGE.replace("%1", study_id).replace("%2", questionnaire.internalId()), 0);
				delete_cookie(COOKIE_RESPONSES_CACHE.replace("%1", study_id).replace("%2", questionnaire.internalId()));
				save_cookie(COOKIE_LAST_COMPLETED.replace("%1", study_id).replace("%2", questionnaire.internalId()), Date.now() / 1000);
			}
			else
				page.loader.error(Lang.get("error_save_responses_failed"));
		});
	};
}



function get_inputHtml(responseType) {
	switch(responseType) {
		case "binary":
			return binary;
		case "date":
			return date;
		case "dynamic_input":
			return dynamic_input;
		case "image":
			return image;
		case "likert":
			return likert;
		case "list_multiple":
			return list_multiple;
		case "list_single":
			return list_single;
		case "number":
			return number;
		case "text":
			return text;
		case "text_input":
			return text_input;
		case "time":
			return time;
		case "va_scale":
			return va_scale;
		case "video":
			return video;
		case "error":
		default:
			return error;
	}
}
function is_itemSkipped(responseType) {
	switch(responseType) {
		case "app_usage":
		case "photo":
			return true;
	}
	return false;
}


let isRegistered = {};
function register(key, html) {
	let componentName ="input_" + key;
	// let componentName = key;
	if(isRegistered.hasOwnProperty(componentName))
		return;
	ko.components.register(componentName, {
		// viewModel: function(params) {
		// 	this.$root = params;
		// },
		viewModel: {
			createViewModel: function(viewModel, componentInfo) {
				viewModel.currentElement = componentInfo.element;
				return viewModel;
			}
		},
		template: html
	});
	isRegistered[componentName] = true;
}

function Questionnaire_viewModel(page, study, questionnaire, qPage, responses_cache) {
	let self = this;
	this.showMissings = ko.observable(false);
	this.has_requiredFields = false
	this.inputs = [];
	
	register("text", text);
	let sourceInputs = qPage.inputs();
	if(qPage.randomized && qPage.randomized()) { //randomize questionnaire if needed
		sourceInputs = sourceInputs.map(function(a){ return {sort: Math.random(), value: a};})
			.sort(function(a, b) {return a.sort - b.sort;})
			.map(function(a) {return a.value;});
	}
	
	for(let i=0, max=sourceInputs.length; i<max; ++i) {
		let input = sourceInputs[i];
		
		if(is_itemSkipped(input.responseType()))
			continue;
		
		let viewModel = new Input_viewModel(study, questionnaire, input, responses_cache);
		this.inputs.push(viewModel);
		
		if(viewModel.required)
			this.has_requiredFields = true;
	}
	
	this.has_missings = function() {
		let inputs = this.inputs;
		let hasMissings = false;
		for(let i=0, max=inputs.length; i<max; ++i) {
			let inputModel = inputs[i];
			if(inputModel.required && inputModel.exportValue() === "") {
				let el = inputModel.currentElement;
				el.scrollIntoView({behavior: 'smooth'});
				page.loader.info(Lang.get("error_missing_requiredField"));
				hasMissings = true;
			}
		}
		self.showMissings(hasMissings);
		return hasMissings;
	}
}

function Input_viewModel(study, questionnaire, input, responses_cache) {
	let self = this;
	let cookieUrl = COOKIE_RESPONSES_CACHE.replace("%1",  study.id()).replace("%2", questionnaire.internalId());
	this.input = input;
	this.currentElement = null;
	let defaultValue = responses_cache.hasOwnProperty(input.name())
		? responses_cache[input.name()]
		: ((input.required && input.required()) || !input.defaultValue || !input.defaultValue() || input.defaultValue().length === 0 ? "" : input.defaultValue());
	
	let responseType = input.hasOwnProperty("responseType") ? input.responseType() : "text_input";
	
	let inputHtml = get_inputHtml(responseType);
	register(responseType, inputHtml);
	
	//TODO
	//for older IOS we would need to add an empty optgroup into the list item to make sure long lines are wrapped. But thats hacky and creates an empty line on other browsers
	//also, Apple already makes us jump through enough hoops...
	//https://stackoverflow.com/questions/19011978/ios-7-doesnt-show-more-than-one-line-when-option-is-longer-than-screen-size
	switch(responseType) {
		case "time":
			let timeValue;
			if(input.forceInt() && defaultValue) {
				let h = Math.round(defaultValue/60);
				let m = (defaultValue%60);
				timeValue = ko.observable(((h<10) ? "0"+h : h) + ":" + ((m<10) ? "0"+m : m));
			}
			else
				timeValue = ko.observable(defaultValue);
			
			this.exportValue = ko.computed(function() {
				if(input.forceInt() && timeValue()) {
					let split = timeValue().split(":");
					return parseInt(split[0]) * 60 + parseInt(split[1])
				}
				else
					return timeValue();
			});
			this.value = timeValue;
			this.required = input.hasOwnProperty("required") && input.required();
			break;
		case "va_scale":
			let has_default = defaultValue !== "";
			this.isClicked = ko.observable(has_default);
			this.value = ko.observable(has_default ? defaultValue : 50);
			this.exportValue = ko.computed(function() {
				if(!self.isClicked())
					return "";
				else
					return self.value();
			});
			this.required = input.hasOwnProperty("required") && input.required();
			
			break;
		case "list_multiple":
			let list_valueChoices = [];
			let listChoices = input.listChoices();
			let defaultArray = defaultValue.split(",");
			for(let i=0, max=listChoices.length; i<max; ++i) {
				let name = listChoices[i];
				list_valueChoices.push([ko.observable(defaultArray.indexOf(name()) !== -1), name]);
			}
			this.exportValue = ko.computed(function() {
				let r = "";
				for(let i=0, max=listChoices.length; i<max; ++i) {
					if(list_valueChoices[i][0]())
						r += listChoices[i]()+",";
				}
				return r;
			});
			this.value = list_valueChoices;
			this.required = input.hasOwnProperty("required") && input.required();
			break;
		case "dynamic_input":
			let study_id = study.id();
			let last_completed = get_cookie(COOKIE_LAST_COMPLETED.replace("%1", study_id).replace("%2", questionnaire.internalId()));
			let cookie_base = input.name() + study_id;
			let index = get_cookie(cookie_base + '_current');
			let choices = input.subInputs();
			let cookie_saved = get_cookie(cookie_base + '_saved');
			
			if(!cookie_saved || cookie_saved <= last_completed || index === null || index >= choices.length) {
				if(input.random()) {
					let cookie_completed = cookie_base + '_completed';
					
					if(index !== null) {
						save_cookie(cookie_completed + index, '1');
						save_cookie[cookie_completed + index] = '1';
					}
					
					let i;
					let choices_left = [];
					for(i = choices.length - 1; i >= 0; --i) {
						if(!get_cookie(cookie_completed + i))
							choices_left.push(i);
					}
					if(!choices_left.length) {
						for(i = choices.length - 1; i >= 0; --i) {
							choices_left.push(i);
							delete_cookie(cookie_completed + i);
						}
					}
					
					index = choices_left[Math.floor(Math.random() * (choices_left.length - 1))];
				}
				else {
					if(index === null || ++index >= choices.length)
						index = 0;
				}
				save_cookie(cookie_base + '_current', index);
				save_cookie(cookie_base + '_saved', Math.round(Date.now()/1000));
			}
			
			let element = choices[index];
			
			
			let subModel = new Input_viewModel(study, questionnaire, element, responses_cache);
			this.value = ko.observable(index);
			this.exportValue = ko.computed(function() {
				// return subModel.exportValue() + "/" + index;
				return subModel.exportValue();
			});
			this.subModel = subModel;
			this.required = element.required ? element.required() : false;
			responses_cache[input.name() + "~index"] = index;
			break;
		default:
			this.value = ko.observable(defaultValue);
			this.exportValue = this.value;
			this.required = input.hasOwnProperty("required") && input.required();
	}
	responses_cache[input.name()] = this.exportValue.peek();
	this.exportValue.subscribe(function(newValue) {
		let study_id = study.id();
		responses_cache[input.name()] = newValue;
		save_cookie(cookieUrl, JSON.stringify(responses_cache));
	});
}