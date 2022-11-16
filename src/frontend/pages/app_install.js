import html from "./app_install.html"
import {Studies} from "../js/main_classes/studies";
import * as ko from "knockout";
import {create_studyUrl, get_base_domain, get_base_url} from "../js/shared/esmira_links";
import {Site} from "../js/main_classes/site";

export function ViewModel(page) {
	this.html = html;
	
	this.promiseBundle = [
		Studies.init(page),
		import("qrcode-generator")
	];
	
	this.showConsent = ko.observable(false);
	
	this.preInit = function(index, studies, {default: qrcode}) {
		let study = Studies.get_current();
		if(!study) {
			page.replace("studies,appInstall");
			this.dataObj = null;
			return;
		}
		
		if(!page.depth || Site.startHash === "appInstall") {
			Site.save_access(page, study.id(), "app_install");
		}
		
		this.dataObj = study;
		page.title(study.title);
		
		this.accessKey = study.accessKeys().length ? (Studies.accessKey().length ? Studies.accessKey() : study.accessKeys()[0]()) : '';
		this.appUrl = create_studyUrl(this.accessKey, study.id(), true, "esmira:");
		this.serverUrl = get_base_domain("");
		
		let qr = qrcode(0, 'L');
		qr.addData(this.appUrl);
		qr.make();
		this.qrUrl = qr.createDataURL(5, 0);
	}
}