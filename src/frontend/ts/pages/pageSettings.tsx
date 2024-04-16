import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {Lang} from "../singletons/Lang";
import {BindObservable} from "../widgets/BindObservable";
import {RichText} from "../widgets/RichText";
import {ObservableLangChooser} from "../widgets/ObservableLangChooser";
import {DashElement} from "../widgets/DashElement";
import {Section} from "../site/Section";
import { CodeEditor } from "../widgets/CodeEditor";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
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
					<div class="center centerChildrenVertically">
						<label class="noTitle noDesc">
							<input type="checkbox" {... BindObservable(page.randomized)}/>
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
								<input type="number" {... BindObservable(page.skipAfterSecs)}/>
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
						<div class="label">
							<small>{Lang.get("page_relevance")}</small>
							{CodeEditor(page.relevance)}
						</div>
					</div>
			})
			
		)
	}
}