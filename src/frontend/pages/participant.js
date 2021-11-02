import html from "./participant.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import {Loader} from "../js/main_classes/loader";
import {participant_isValid} from "../js/shared/participant";
import {save_cookie} from "../js/helpers/basics";
import {COOKIE_PARTICIPANT} from "../js/variables/cookie_names";
import {Site} from "../js/main_classes/site";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	page.title(Lang.get("user_id"));
	
	this.dataObj = null;
	
	let study;
	this.preInit = function() {
		study = Studies.get_current();
		if(!study) {
			page.replace("studies,participant");
			this.dataObj = null;
			return;
		}
		this.dataObj = study
		
		if(study.contactEmail)
			this.extraContent = "<a class=\"small_text\" href=\"mailto:"+study.contactEmail()+"\">"+Lang.get('contactEmail')+"</a>";
	};
	
	this.create_participant = function() {
		let new_participant = document.getElementById("participant_input").value;
		if(participant_isValid(new_participant)) {
			let study_id = study.id();
			
			save_cookie(COOKIE_PARTICIPANT.replace("%d", study_id), new_participant);
			
			Site.save_dataset(page, "joined", new_participant);
			
			page.replace("attend");
		}
		else
			Loader.info(Lang.get('error_participant_wrong_format'));
	};
}