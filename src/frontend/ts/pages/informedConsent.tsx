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
		return Lang.get("informed_consent")
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <pre class="wrap">
			{study.informedConsentForm.get()}
		</pre>
	}
}