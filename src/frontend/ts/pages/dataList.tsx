import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import questionnaireSvg from "../../imgs/icons/questionnaire.svg?raw"
import backupSvg from "../../imgs/icons/backup.svg?raw"
import {Section} from "../site/Section";
import {FILE_ADMIN, FILE_MEDIA, FILE_CREATE_MEDIA, FILE_RESPONSES} from "../constants/urls";
import {Study} from "../data/study/Study";
import {TitleRow} from "../widgets/TitleRow";
import {Requests} from "../singletons/Requests";
import {safeConfirm} from "../constants/methods";
import {Questionnaire} from "../data/study/Questionnaire";
import {BtnTrash} from "../widgets/BtnWidgets";

interface DataLineEntry {
	title: string
	fileName: string | number
	previewId?: number
}

export class Content extends SectionContent {
	private is_generating_zip: boolean = false
	private readonly backupEntries: DataLineEntry[]
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getStudyPromise(),
			Requests.loadJson(`${FILE_ADMIN}?type=ListData&study_id=${section.getStaticInt("id") ?? 0}`),
			section.getAdmin().init()
		]
	}
	
	constructor(section: Section, study: Study, dataEntries: string[]) {
		super(section)
		
		const questionnaires = study.questionnaires.get()
		const questionnaireIndex: Record<number, Questionnaire> = {};
		for(let i = questionnaires.length - 1; i >= 0; --i) {
			const questionnaire = questionnaires[i]
			questionnaireIndex[questionnaire.internalId.get()] = questionnaire
		}
		
		this.backupEntries = dataEntries
			.filter((fileName) => !questionnaireIndex.hasOwnProperty(parseInt(fileName)))
			.map((fileName) => {
				const [match, date, internalId] = fileName.match(/^(\d{4}-\d{2}-\d{2})_(\d+)$/) ?? []
				if(match != null) {
					if(questionnaireIndex.hasOwnProperty(parseInt(internalId)))
						return {title: `${date} ${questionnaireIndex[parseInt(internalId)].getTitle()}`, fileName: fileName}
				}
				
				return {title: fileName, fileName: fileName}
			})
	}
	
	public title(): string {
		return Lang.get("data_table")
	}
	
	private async backupStudy(study: Study): Promise<any> {
		if(!confirm(Lang.get("confirm_backup", study.title.get())))
			return
		
		await this.section.loader.loadJson(`${FILE_ADMIN}?type=BackupStudy`, "post", `study_id=${ study.id.get()}`)
		await this.section.reload()
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	private async emptyData(study: Study): Promise<any> {
		if(!safeConfirm(Lang.get("confirm_delete_data", study.title.get())))
			return;
		
		await this.section.loader.loadJson(`${FILE_ADMIN}?type=EmptyData`, "post", `study_id=${study.id.get()}`)
		await this.section.reload()
		
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return study.version.get() == 0
			? <div></div>
			: <div>
				{this.getDataLineView(
					"general",
					study,
					[
						{title: Lang.get("events_csv_title"), fileName: "events"},
						{title: Lang.get("web_access_csv_title"), fileName: "web_access"}
					]
				)}

				{TitleRow(Lang.getWithColon("questionnaires"))}
				{this.getDataLineView(
					"questionnaire",
					study,
					study.questionnaires.get().map((questionnaire) => {
						return {title: questionnaire.getTitle(), fileName: questionnaire.internalId.get(), previewId: questionnaire.internalId.get()}
					})
				)}

				{study.hasMedia() &&
					<div>
						{TitleRow(Lang.getWithColon("media_download"))}
						<a class="spacingRight" onclick={this.waitForDownload.bind(this, study)}>
							{m.trust(downloadSvg)}
							<span id="mediaZipSpan" class="spacingLeft">media.zip</span>
						</a>
					</div>
				}

				{TitleRow(Lang.getWithColon("backups"))}
				<div class="verticalPadding">
					<a class="horizontal spacingRight" onclick={this.backupStudy.bind(this, study)}>
						{m.trust(backupSvg)}
						<span>{Lang.get("create_backup")}</span>
					</a>
				</div>
				<br/>
				{this.getDataLineView(
					"backup",
					study,
					this.backupEntries
				)}

				<br/>
				{this.hasPermission("write", study.id.get()) &&
					<div class="verticalPadding highlight">
						{BtnTrash(this.emptyData.bind(this, study), Lang.get("empty_data"))}
					</div>

				}
			</div>
	}

	private getDataLineView(sectionValue: string, study: Study, list: DataLineEntry[]): Vnode<any, any>[] {
		return list.map((entry) =>
			<div>
				<div>
					<a class="spacingRight" href={FILE_RESPONSES.replace('%1', study.id.get().toString()).replace('%2', entry.fileName.toString())} download={entry.title} title={Lang.get("download")}>
						{m.trust(downloadSvg)}
					</a>
					{entry.previewId &&
						<span>
							<a class="spacingRight" href={this.getUrl(`demo:static,qId:${entry.previewId}`)}>
								{m.trust(questionnaireSvg)}
							</a>
						</span>
					}

					<a href={this.getUrl(`dataView:${sectionValue},${typeof entry.fileName == "number" ? "qId" : "file"}:${entry.fileName}`)}>{entry.title}</a>
				</div>
			</div>
		)
	}

	private waitForDownload(study: Study) {
		if (this.is_generating_zip) {
			return
		}
		this.is_generating_zip = true
		const eventSource = new EventSource(`${FILE_CREATE_MEDIA.replace("%1", study.id.get().toString())}`)
		const mediaZipSpan = document.getElementById("mediaZipSpan")

		eventSource.addEventListener('progress', e => {
			if(mediaZipSpan != undefined)
				mediaZipSpan.innerText = "media.zip (%1 ... %2\%)".replace("%1", Lang.get("generating")).replace("%2", e.data)
		})
		eventSource.addEventListener('finished', e => {
			this.is_generating_zip = false
			eventSource.close()

			if(mediaZipSpan != undefined)
				mediaZipSpan.innerText = "media.zip"
			let element = document.createElement('a')
			element.setAttribute('href', `${FILE_MEDIA.replace("%1", study.id.get().toString())}`)
			element.setAttribute('download', 'media.zip')
			document.body.appendChild(element);
			element.click();
			document.body.removeChild(element);
		})
	}
}