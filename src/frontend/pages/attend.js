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
import {Requests} from "../js/main_classes/requests";
import {FILE_GET_QUESTIONNAIRE, FILE_SAVE_DATASET} from "../js/variables/urls";
import {currentLangCode} from "../js/shared/lang_configs";


export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [
		Studies.init(page),
	];
	
	this.pageType = Site.valueIndex.hasOwnProperty("demo") ? "demo" : "questionnaire";
	this.wasFinished = ko.observable(false);
	
	let study, questionnaire, noCookiesSid;
	
	let isDemo = false
	
	this.preInit = function({id, q, qId, demo}, studies) {
		isDemo = !!demo;
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
		
		if(!questionnaire) {
			this.dataObj = null;
			page.replace("sOverview");
			return;
		}
		
		//define variables:
		this.dataObj = questionnaire;
		this.study = study;
		this.active = ko.computed(function() {return self.pageType === 'demo' || (Studies.questionnaire_isActive(questionnaire) && !self.wasFinished())});
		if(study.contactEmail)
			this.extraContent = "<a class=\"small_text\" href=\""+study.contactEmail()+"\">"+Lang.get('contactEmail')+"</a>";
	};
	this.postInit = function() {
		document.forms["pageContent"].addEventListener("submit", function(e) {
			e.preventDefault();
			e.stopPropagation();
			self.loadPage(e.submitter.name);
			return false;
		});
		
		self.loadPage();
	}
	
	this.scrollUp = function() {
		window.setTimeout(function() {
			self.viewModel_page().inputs[0].currentElement.scrollIntoView({behavior: 'smooth'});
		}, 0);
	};
	this.loadPage = function(type) {
		let formData = new FormData(document.forms["pageContent"]);
		let data = type || "load";
		for(let pair of formData.entries()) {
			data += "&" + pair[0] + "=" + pair[1];
		}
		
		return page.loader.loadRequest(
			FILE_GET_QUESTIONNAIRE
				.replace("%d1", study.id())
				.replace("%d2", questionnaire.internalId())
				.replace("%s1", Studies.accessKey())
				.replace("%s2", currentLangCode)
				.replace("%s3", (isDemo ? "demo=1&" : "") + (noCookiesSid || "")),
			false, "post", data
		).then(function({dataType, sid, currentPageInt, pageHtml, pageTitle, missingInput}) { //TODO: sid, missingInput
			noCookiesSid = sid;
			
			document.getElementById("pageContent").innerHTML = pageHtml;
			page.title(pageTitle);
			
			if(dataType === "questionnaire") {
				let inputs = questionnaire.pages()[currentPageInt].inputs()
				for(let i = inputs.length-1; i >= 0; --i) {
					handleInput(inputs[i]);
				}
				if(missingInput) {
					console.log(missingInput);
					let missingElement = document.getElementById("item-"+missingInput);
					missingElement.scrollIntoView({behavior: 'smooth'});
					missingElement.style.outline = "5px solid red";
					window.setTimeout(function() {
						missingElement.style.outline = "unset";
					}, 3000)
				}
			}
			else if(dataType === "finished") {
				self.wasFinished(true);
			}
		});
	}
}

function handleInput(input) {
	let formElements = document.forms["pageContent"].elements;
	let child = formElements["responses["+input.name()+"]"];
	
	switch(input.responseType()) {
		case "va_scale":
			if(child.getAttribute("no-value")) {
				child.classList.add("not-clicked");
				let wasClicked = function(e) {
					child.classList.remove("not-clicked");
					//ios-track-clickable-workaround:
					//let el = e.target;
					//child.value = Math.min(el.max, Math.max(1, Math.round((el.max / el.offsetWidth) * (e.offsetX))));
					return true;
				}
				child.addEventListener("mousedown", wasClicked);
				child.addEventListener("touchstart", wasClicked);
			}
			if(input.showValue()) {
				child.addEventListener("change", function() {
					child.previousElementSibling.innerText = child.value;
				});
			}
			break;
		default:
			break;
	}
}