import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {ObservableLangChooser} from "../widgets/ObservableLangChooser";
import {BindObservable} from "../widgets/BindObservable";
import {RichText} from "../widgets/RichText";
import {Section} from "../site/Section";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	public title(): string {
		return Lang.get("study_description")
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			<label>
				<small>{Lang.getWithColon("title")}</small>
				<input type="text" {...BindObservable(study.title)}/>
				{ObservableLangChooser(study)}
			</label>

			<label>
				<small>{Lang.getWithColon("study_tag")}</small>
				<input type="text" {...BindObservable(study.studyTag)}/>
				{ObservableLangChooser(study)}
			</label>
			
			<label>
				<small>{Lang.getWithColon("contactEmail")}</small>
				<input type="text" {...BindObservable(study.contactEmail)}/>
				{ObservableLangChooser(study)}
			</label>

			
			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("description")}</small>
				{RichText(study.studyDescription)}
				{ObservableLangChooser(study)}
			</div>
			
			<label class="spacingTop line">
				<small>{Lang.getWithColon("informed_consent")} ({Lang.getWithColon("can_be_left_empty")})</small>
				<textarea {...BindObservable(study.informedConsentForm)}></textarea>
				{ObservableLangChooser(this.getStudyOrThrow())}
			</label>
			
			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("postInstallInstructions")}</small>
				{RichText(study.postInstallInstructions)}
				{ObservableLangChooser(this.getStudyOrThrow())}
			</div>
			
			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("webInstallInstructions")}</small>
				{RichText(study.webInstallInstructions)}
				{ObservableLangChooser(this.getStudyOrThrow())}
			</div>
			
			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("chooseUsernameInstructions")}</small>
				{RichText(study.chooseUsernameInstructions)}
				{ObservableLangChooser(this.getStudyOrThrow())}
			</div>
			
			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("webQuestionnaireCompletedInstructions")}</small>
				{RichText(study.webQuestionnaireCompletedInstructions)}
				{ObservableLangChooser(this.getStudyOrThrow())}
			</div>
		</div>
	}
}