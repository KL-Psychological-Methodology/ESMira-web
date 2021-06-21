import {Site} from './main_classes/site.js';
import {Lang} from "./main_classes/lang";
import {fillDefaults} from "./variables/defaults";
import {isCompatible, toggleUrl} from "./helpers/compatible";

//load variables so they are already available everywhere
import * as Constants from "./variables/constants";

export function init(startHash, serverName, serverVersion, server_accessKey, langCode, type, langObj) {
	if(type)
		document.body.classList.add(type);
	if(process.env.NODE_ENV !== 'production') {
		document.body.classList.add("localhost");
	}
	
	if(!isCompatible()) { //detect IE, very old browsers or IE. It also detects IE
		console.log("You shall not pass!");
		toggleUrl();
		return;
	}
	
	Lang.init(langObj, langCode);
	fillDefaults();
	
	Site.init(serverName, startHash, serverVersion, server_accessKey);
	// ko.applyBindings(root);
	//
	// window.onbeforeunload = function(){
	//     return root.studies.any_study_changed() ? Lang.confirm_leave_page_unsaved_changes : undefined;
	// };
	//
	//
	document.body.classList.add("is_init");
}