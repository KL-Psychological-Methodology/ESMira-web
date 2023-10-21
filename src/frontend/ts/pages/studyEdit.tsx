import {SectionAlternative, SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import studyDescSvg from "../../imgs/dashIcons/studyDesc.svg?raw"
import addSvg from "../../imgs/icons/add.svg?raw"
import editSvg from "../../imgs/icons/change.svg?raw"
import alarmsSvg from "../../imgs/dashIcons/alarms.svg?raw"
import calendarSvg from "../../imgs/icons/calendar.svg?raw"
import chartsSvg from "../../imgs/dashIcons/charts.svg?raw"
import sumScoresSvg from "../../imgs/dashIcons/sumScores.svg?raw"
import langSvg from "../../imgs/dashIcons/lang.svg?raw"
import publishSvg from "../../imgs/dashIcons/publish.svg?raw"
import rewardsSvg from "../../imgs/dashIcons/rewards.svg?raw"
import settingsSvg from "../../imgs/dashIcons/settings.svg?raw"
import {Study} from "../data/study/Study";
import {ObservableLangChooser} from "../widgets/ObservableLangChooser";
import {ObserverId} from "../observable/BaseObservable";
import {Section} from "../site/Section";
import {AddDropdownMenus} from "../helpers/AddDropdownMenus";
import {UrlAlternatives} from "../helpers/UrlAlternatives";

export class Content extends SectionContent {
	private readonly studyObserverId: ObserverId
	private readonly addDropdownMenus = new AddDropdownMenus(this)
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getStudyPromise()
		]
	}
	
	constructor(section: Section, study: Study) {
		super(section)
		this.updateSaveState()
		
		this.studyObserverId = study.addObserver(this.updateSaveState.bind(this))
		this.section.siteData.dynamicCallbacks.save = async () => {
			await this.section.loader.showLoader(
				this.section.siteData.studyLoader.saveStudy(this.getStudyOrThrow())
			)
		}
		this.section.siteData.dynamicCallbacks.publish = async () => {
			await this.section.loader.showLoader(
				this.section.siteData.studyLoader.publishStudy(this.getStudyOrThrow())
			)
		}
	}
	
	public title(): string {
		return Lang.get("edit_studies")
	}
	public titleExtra(): Vnode<any, any> | null {
		const study = this.getStudyOrThrow()
		if(study.langCodes.get().length > 1)
			return ObservableLangChooser(study)
		else return null
	}
	
	public hasAlternatives(): boolean {
		return true
	}
	public getAlternatives(): SectionAlternative[] | null {
		return UrlAlternatives.studyAlternatives(this, "edit")
	}
	
	private updateSaveState(): void {
		const study = this.getStudyOrThrow()
		this.setDynamic("showSaveButton", study.isDifferent())
		this.setDynamic("showPublishButton", study.newChanges.get())
	}
	private clearSaveState(): void {
		this.setDynamic("showSaveButton", false)
		this.setDynamic("showPublishButton", false)
	}
	
	private async addQuestionnaire(e: MouseEvent): Promise<void> {
		const study = this.getStudyOrThrow()
		return this.addDropdownMenus.addQuestionnaire(study, e.target as Element)
	}
	
	public getView(): Vnode<any, any> {
		const disabled = !this.getStudyOrThrow().questionnaires.get().length
		
		return DashRow(
			DashElement(null, {template: {title: Lang.get("study_description"), icon: m.trust(studyDescSvg) }, href: this.getUrl("studyDesc")}),
			DashElement(null,
				{floating: true, template: {title: Lang.get("create"), icon: m.trust(addSvg) }, onclick: this.addQuestionnaire.bind(this)},
				{template: {title: Lang.get("edit_questionnaire"), icon: m.trust(editSvg) }, href: this.getUrl("qEdit")}
			),
			DashElement(null, {template: {title: Lang.get("filter_and_trigger"), icon: m.trust(alarmsSvg) }, href: this.getUrl("filterTrigger"), disabled: disabled}),
			DashElement(null, {template: {title: Lang.get("calendar"), icon: m.trust(calendarSvg) }, href: this.getUrl("calendar"), disabled: disabled}),
			DashElement(null, {template: {title: Lang.get("create_charts"), icon: m.trust(chartsSvg) }, href: this.getUrl("charts"), disabled: disabled}),
			DashElement(null, {template: {title: Lang.get("calculate_sumScores"), icon: m.trust(sumScoresSvg) }, href: this.getUrl("sumScores"), disabled: disabled}),
			DashElement(null, {template: {title: Lang.get("languages_and_randomGroups"), icon: m.trust(langSvg) }, href: this.getUrl("langGroups")}),
			DashElement(null, {template: {title: Lang.get("publish_study"), icon: m.trust(publishSvg) }, href: this.getUrl("publish")}),
			DashElement(null, {template: {title: Lang.get("reward_system"), icon: m.trust(rewardsSvg), noCompatibilityIcon: ["Web"] }, href: this.getUrl("rewards")}),
			DashElement(null, {template: {title: Lang.get("study_settings"), icon: m.trust(settingsSvg) }, href: this.getUrl("studySettings")}),
			DashElement(null, {template: {title: Lang.get("edit_source") }, href: this.getUrl("source")}),
		)
	}
	
	public destroy(): void {
		this.studyObserverId.removeObserver()
		this.clearSaveState()
		this.section.siteData.dynamicCallbacks.save = undefined
		this.section.siteData.dynamicCallbacks.publish = undefined
		super.destroy();
	}
}