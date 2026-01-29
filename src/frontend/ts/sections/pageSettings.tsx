import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../components/DashRow";
import {Lang} from "../singletons/Lang";
import {BindObservable, ConstrainedNumberTransformer} from "../components/BindObservable";
import {RichText} from "../components/RichText";
import {ObservableLangChooser} from "../components/ObservableLangChooser";
import {DashElement} from "../components/DashElement";
import {CodeEditor} from "../components/CodeEditor";
import {NotCompatibleIcon} from "../components/NotCompatibleIcon";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}

	public title(): string {
		const pageI = this.getStaticInt("pageI") ?? 0
		return Lang.get("edit_page_x", pageI + 1)
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const page = this.getQuestionnaireOrThrow().pages.get()[this.getStaticInt("pageI") ?? 0]
		return DashRow(
			DashElement(null, {
				content:
					<div class="horizontal hAlignCenter vAlignCenter">
						<label class="noTitle noDesc">
							<input type="checkbox" {...BindObservable(page.randomized)} />
							<span>{Lang.get("randomize_page")}</span>
						</label>
					</div>
			}),
			DashElement(null, {
				content:
					<div>
						<small>{Lang.get("skip_page_after_secs_info")}</small>
						<div class="center spacingTop">
							<label>
								<small>{Lang.get("skip_page_after_secs")}</small>
								<input type="number" min="0" {...BindObservable(page.skipAfterSecs, new ConstrainedNumberTransformer(0, undefined))} />
								<span>{Lang.get("seconds")}</span>
							</label>
						</div>
					</div>
			}),
			DashElement("stretched", {
				content:
					<div>
						<div class="fakeLabel line noDesc">
							<small>{Lang.get("header")}</small>
							{RichText(page.header)}
							{ObservableLangChooser(study)}
						</div>
					</div>
			}),
			DashElement("stretched", {
				content:
					<div>
						<div class="fakeLabel line noDesc">
							<small>{Lang.get("footer")}</small>
							{RichText(page.footer)}
							{ObservableLangChooser(study)}
						</div>
					</div>
			}),
			DashElement("stretched", {
				content:
					<div>
						<div class="fakeLabel line noDesc">
							<small>{Lang.get("page_relevance")}{NotCompatibleIcon("Web")}</small>
							{CodeEditor(page.relevance)}
						</div>
					</div>
			})

		)
	}
}