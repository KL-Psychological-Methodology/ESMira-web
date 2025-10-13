import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {ObservableLangChooser} from "../components/ObservableLangChooser";
import {BindObservable} from "../components/BindObservable";
import {RichText} from "../components/RichText";
import {RegexTextInput} from "../components/RegexTextInput";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}
	public title(): string {
		return Lang.get("study_description")
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			<label>
				<small>{Lang.getWithColon("title")}</small>
				<input type="text" {...BindObservable(study.title)} />
				{ObservableLangChooser(study)}
			</label>

			<label>
				<small>{Lang.getWithColon("study_tag")}</small>
				<input type="text" {...BindObservable(study.studyTag)} />
				{ObservableLangChooser(study)}
			</label>

			{
				RegexTextInput(
					Lang.getWithColon("contactEmail"),
					study.contactEmail,
					/^[\w\-\.]+@([\w-]+\.)+[\w-]{2,}$/,
					Lang.get("validator_warning_email"))
			}

			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("description")}</small>
				{RichText(study.studyDescription)}
				{ObservableLangChooser(study)}
			</div>

			<label class="spacingTop line">
				<small>{Lang.get("informed_consent")} ({Lang.get("can_be_left_empty")}):</small>
				<textarea {...BindObservable(study.informedConsentForm)}></textarea>
				{ObservableLangChooser(this.getStudyOrThrow())}
			</label>

			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("postInstallInstructions")}</small>
				{RichText(study.postInstallInstructions)}
				{ObservableLangChooser(this.getStudyOrThrow())}
			</div>

			<div class="fakeLabel spacingTop line">
				<small>{Lang.get("faqs")} ({Lang.get("can_be_left_empty")}):</small>
				{RichText(study.faq)}
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

			<div class="fakeLabel spacingTop line">
				<small>{Lang.getWithColon("post_study_note")}</small>
				{RichText(study.postStudyNote)}
				{ObservableLangChooser(this.getStudyOrThrow())}
			</div>
		</div>
	}
}