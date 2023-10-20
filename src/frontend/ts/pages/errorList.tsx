import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN} from "../constants/urls";
import {ErrorReportInfo} from "../data/errorReports/ErrorReportInfo";
import commentSvg from "../../imgs/icons/comment.svg?raw"
import {BtnOk, BtnTrash} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	private knownReports: ErrorReportInfo[] = []
	private newReports: ErrorReportInfo[] = []
	
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=ListErrors`)
		]
	}
	constructor(section: Section, reports: ErrorReportInfo[]) {
		super(section)
		
		this.sortErrorReports(reports)
	}
	
	public title(): string {
		return Lang.get("errorReports")
	}
	
	private async reloadErrorReports(): Promise<void> {
		const reports = await this.section.loader.loadJson(`${FILE_ADMIN}?type=ListErrors`)
		this.sortErrorReports(reports)
	}
	
	private sortErrorReports(reports: ErrorReportInfo[]) {
		this.knownReports = []
		this.newReports = []
		
		reports = reports.sort((a, b) => {
			return a.timestamp - b.timestamp;
		})
		
		for(const report of reports) {
			report.printName = this.getName(report)
			
			if(report.seen)
				this.knownReports.push(report)
			else
				this.newReports.push(report)
		}
		this.getTools().hasErrors = !!this.newReports.length
	}
	
	private getName(report: ErrorReportInfo): string {
		const date = new Date(report.timestamp).toLocaleString()
		return report.note ? `${report.note} (${date})` : date
	}
	
	private async deleteReport(report: ErrorReportInfo): Promise<void> {
		if(!confirm(Lang.get("confirm_delete_error", this.getName(report))))
			return
		
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=DeleteError`,
			"post",
			`timestamp=${report.timestamp}&note=${report.note}&seen=${report.seen ? 1 : 0}`
		)
		await this.reloadErrorReports()
	}
	private async markReportAsSeen(report: ErrorReportInfo): Promise<void> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ChangeError`,
			"post",
			`timestamp=${report.timestamp}&note=${report.note}&seen=1`
		)
		await this.reloadErrorReports()
	}
	private async addNote(report: ErrorReportInfo): Promise<void> {
		const newNote = prompt(Lang.get("prompt_comment"), report.note)
		if(!newNote)
			return
		
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ChangeError`,
			"post",
			`timestamp=${report.timestamp}&note=${newNote}&seen=${report.seen ? 1 : 0}`
		)
		await this.reloadErrorReports()
	}
	
	public getView(): Vnode<any, any> {
		return <div>
			{this.getErrorReportList(this.newReports)}
			{this.knownReports.length != 0 && this.newReports.length != 0 &&
				<hr/>
			}
			{this.getErrorReportList(this.knownReports)}
		</div>
	}
	
	private getErrorReportList(reports: ErrorReportInfo[]): Vnode<any, any> {
		return <div class="listParent">
			<div class="listChild error_list">
				{reports.map((report) =>
					<div>
						{BtnTrash(this.deleteReport.bind(this, report))}
						{!report.seen &&
							BtnOk(this.markReportAsSeen.bind(this, report))
						}
						<a onclick={this.addNote.bind(this, report)}>
							{m.trust(commentSvg)}
						</a>
						<a href={this.getUrl(`errorView,timestamp:${report.timestamp},note:${btoa(report.note)}`)}>{report.printName}</a>
						{report.seen &&
							<span class="extraNote">{Lang.get("seen")}</span>
						}
					</div>
				)}
			</div>
		</div>
	}
}