import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { BindObservable, ConstrainedNumberTransformer } from "../components/BindObservable";
import { TitleRow } from "../components/TitleRow";
import { NotCompatibleIcon } from "../components/NotCompatibleIcon";
import { ChangeLanguageList } from "../components/ChangeLanguageList";
import { SectionData } from "../site/SectionData";

export class Content extends SectionContent {
	private changeLanguageList: ChangeLanguageList

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}

	constructor(sectionData: SectionData) {
		super(sectionData)
		this.changeLanguageList = new ChangeLanguageList(() => {
			return this.getStudyOrThrow()
		})
	}

	public preInit(): Promise<void> {
		return this.changeLanguageList.promise
	}

	public title(): string {
		return Lang.get("languages_and_randomGroups")
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