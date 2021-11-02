import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import {bindEvent, createElement} from "../js/helpers/basics";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {repairStudy} from "../js/helpers/updater";
import {Defaults} from "../js/variables/defaults";
import JSONEditor from "jsoneditor"
import "jsoneditor/dist/jsoneditor.css"

export function ViewModel(page) {
	this.promiseBundle = [
		Studies.init(page),
	];
	this.extraContent = "<lang-options params='onChange: $root.onChangeLang'></lang-options>";
	page.title(Lang.get("study_source"));
	
	let study, editor, btn;
	this.postInit = function({id}, studies) {
		let el = createElement("div", "height: 100%");
		page.contentEl.appendChild(el);
		
		btn = createElement(
			"input",
			"position: absolute; right: 5px; bottom: 5px; float: right; z-index: 10;",
			{type: "button", value: Lang.get("apply"), className: "floatingBtn hidden"}
		);
		page.contentEl.appendChild(btn);
		
		
		
		study = studies[id];
		editor = new JSONEditor(
			el,
			{
				mode: "tree",
				modes: ["tree", 'code'],
				onChange: function() {
					btn.classList.remove("hidden");
				}
			});
		
		bindEvent(btn, "click", function() {
			let json;
			try {
				json = JSON.parse(editor.getText());
			}
			catch(e) {
				page.loader.error(e);
				console.error(e);
				return;
			}
			json.id = study.id();
			repairStudy(json);
			
			// console.log(OwnMapping.toJS(study));
			// console.log(JSON.parse(JSON.stringify(json)));
			
			OwnMapping.update(study, json, Defaults.studies);
			
			// console.log(OwnMapping.toJS(study));
			
			btn.classList.add("hidden");
		});
		editor.set(OwnMapping.toJS(study));
	};
	
	
	
	this.onChangeLang = function() {
		editor.set(OwnMapping.toJS(study));
		btn.classList.add("hidden");
	}
}