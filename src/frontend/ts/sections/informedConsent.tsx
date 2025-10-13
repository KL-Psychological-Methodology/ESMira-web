import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
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