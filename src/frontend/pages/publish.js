import html from "./publish.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {Admin} from "../js/main_classes/admin";
import {
	check_accessKeyFormat,
	create_appUrl,
	create_questionnaireUrl,
	create_studyUrl
} from "../js/shared/esmira_links";



export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [
		Studies.init(page),
		import("qrcode-generator")
	];
	page.title(Lang.get("publish_study"));
	
	
	this.accessKey = ko.observable("");
	this.urlBox = [];
	this.selectedUrl = ko.observable("");
	this.size = ko.observable(5);
	this.qrUrl = ko.observable("");
	
	
	this.preInit = function({id}, studies, {default: qrcode}) {
		let study = studies[id];
		let questionnaires = study.questionnaires();
		this.dataObj = study;
		
		let get_computed = function(fu, values, onlyIfAccessKey) {
			return ko.computed(function() {
				let accessKey = self.accessKey();
				if(onlyIfAccessKey && !accessKey)
					return null;
				values[0] = accessKey;
				return fu.apply(null, values);
				// return (accessKey && accessKey.length) ? url.replace("%", accessKey+"+") : url.replace("%", "");
			});
		}
		
		let urlBox = [
			{
				title: questionnaires.length >= 1 ? Lang.get("questionnaire_view") : Lang.get("study"),
				urls: [
					get_computed(create_studyUrl, [false, id]),
					get_computed(create_studyUrl, [false, id, true], true)
				]
			},
			{
				title: Lang.get('colon_app_installation_instructions'),
				urls: [
					get_computed(create_appUrl, [false, id]),
					get_computed(create_appUrl, [false, id, true], true)
				]
			}
		];
		for(let i=0, max=questionnaires.length; i<max; ++i) {
			let questionnaire = questionnaires[i];
			urlBox.push({
				title: questionnaire.title(),
				// url: get_computed(get_base_url()+"%"+id+"."+i)
				urls: [get_computed(create_questionnaireUrl, [false, questionnaire.internalId()])]
			});
		}
		this.urlBox = urlBox;
		
		
		let generate_qrUrl = function() {
			let typeNumber = 0;
			let errorCorrectionLevel = 'L';
			let qr = qrcode(typeNumber, errorCorrectionLevel);
			qr.addData(self.selectedUrl());
			qr.make();
			
			self.qrUrl(qr.createDataURL(self.size() ? self.size() : 5, 0));
		};
		
		if(study.accessKeys().length)
			this.accessKey(study.accessKeys()[0]());
		this.accessKey.subscribe(generate_qrUrl);
		this.selectedUrl.subscribe(generate_qrUrl);
		this.size.subscribe(generate_qrUrl);
		
		this.selectedUrl(urlBox[0].urls[0]());
	};
	
	
	let listTools = Admin.tools.get_listTools(page);
	this.add_accessKey = function(study) {
		listTools.add_prompted(study.accessKeys, function(s) {
			if(check_accessKeyFormat(s))
				return false;
			else
				return Lang.get("error_accessKey_wrong_format");
		});
	}
	this.remove_accessKey = function(study) {
		let a = study.accessKeys();
		let key = self.accessKey();
		let i = a.length-1;
		for(; i>=0; --i) {
			if(a[i]() === key)
				break;
		}
		listTools.remove_from_list(self.dataObj.accessKeys, i);
	};
}