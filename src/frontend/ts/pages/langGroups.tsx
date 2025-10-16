import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { BindObservable, ConstrainedNumberTransformer } from "../widgets/BindObservable";
import { TitleRow } from "../widgets/TitleRow";
import { NotCompatibleIcon } from "../widgets/NotCompatibleIcon";
import { Section } from "../site/Section";
import { ChangeLanguageList } from "../widgets/ChangeLanguageList";

export class Content extends SectionContent {
	private changeLanguageList: ChangeLanguageList

	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}

	constructor(sitePage: Section) {
		super(sitePage)
		this.changeLanguageList = new ChangeLanguageList(() => {
			return this.getStudyOrThrow()
		})
	}

	public preInit(): Promise<void> {
		return this.changeLanguageList.promise
	}

	public title(): string {
		return Lang.get("study_description")
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			{TitleRow(
				<div>
					<span class="spacingRight">{Lang.get("randomGroups")}</span>
					{NotCompatibleIcon("Web")}
				</div>
			)}
			<div class="smallText">{Lang.get("info_randomGroups")}</div>

			<label class="spacingTop">
				<small>{Lang.get("group_count")}</small>
				<input type="number" min="1" {...BindObservable(study.randomGroups, new ConstrainedNumberTransformer(1, undefined))} />
			</label>

			{TitleRow(Lang.getWithColon("additional_languages"))}
			<div class="smallText spacingBottom">{Lang.get("info_languages")}</div>
			{this.changeLanguageList.getView()}
		</div>
	}
}