import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN} from "../constants/urls";
import {ErrorReportInfo} from "../data/errorReports/ErrorReportInfo";

interface ErrorReportComponentOptions {
	report: string
}
class ErrorReportComponent implements Component<ErrorReportComponentOptions, any> {
	private rootViewOffset: number = 0
	private errorLines: HTMLElement[] = []
	private warningLines: HTMLElement[] = []
	private logLines: HTMLElement[] = []
	private stickyLines: Record<number, HTMLElement> = {}
	
	private addLineContent(view: HTMLElement, innerText: string, cssText: string): void {
		const span = document.createElement("span")
		span.style.cssText = cssText
		span.innerText = innerText
		view.appendChild(span)
	}
	
	private clickLine(line: HTMLElement, index: number): void {
		if(line.classList.contains("sticky")) {
			line.classList.remove("sticky");
			line.style.top = "0"
			line.style.bottom = "0"
			
			delete this.stickyLines[index]
		}
		else {
			this.stickyLines[index] = line
			line.classList.add("sticky")
		}
		this.updateStickyPositions()
	}
	
	private updateStickyPositions(): void {
		let height = -10;
		
		for(let index in this.stickyLines) {
			const view = this.stickyLines[index]
			
			view.style.top = `${height}px`
			height += view.clientHeight
		}
		
		let heightBottom = 0;
		for(let index in this.stickyLines) {
			const view = this.stickyLines[index]
			
			heightBottom += view.clientHeight;
			view.style.bottom = `${height-heightBottom}px`
		}
	}
	
	
	
	private clickLineCategory(lines: HTMLElement[]): void {
		for(let line of lines) {
			const rect = line.getBoundingClientRect()
			if(rect.top - this.rootViewOffset > 0) {
				line.scrollIntoView({behavior: 'smooth'})
				return
			}
			
			lines[0].scrollIntoView({behavior: 'smooth'})
		}
		console.log(this.warningLines[1].getBoundingClientRect())
	}
	
	public oncreate(vNode: VnodeDOM<ErrorReportComponentOptions, any>): void {
		const infoView = vNode.dom.getElementsByClassName("errorReportHeader")[0] as HTMLElement
		const rootView = vNode.dom.getElementsByClassName("errorReportList")[0] as HTMLElement
		
		const lines = vNode.attrs.report.split("\n\n")
		this.rootViewOffset = rootView.getBoundingClientRect().top
		
		infoView.innerText = lines[0]
		
		for(let i = 1; i < lines.length; i++) {
			const line = lines[i]
			let content = line.trim()
			const view = document.createElement("div")
			view.classList.add("line")
			
			const pre = document.createElement("pre")
			view.appendChild(pre)
			
			if(content.startsWith("Error:")) {
				this.errorLines.push(view)
				this.addLineContent(pre, "Error:", "color: red; font-weight: bold;")
				content = content.substring(6)
			}
			else if(content.startsWith("Warning:")) {
				this.warningLines.push(view)
				this.addLineContent(pre, "Warning:", "color: orange;")
				content = content.substring(8)
			}
			else if(content.startsWith("Log:")) {
				this.logLines.push(view)
				this.addLineContent(pre, "Log:", "font-weight: bold;")
				content = content.substring(4)
			}
			else {
				this.logLines.push(view)
			}
			
			if(content.indexOf("Cold starting app") != -1) {
				view.classList.add("divider");
			}
			const span = document.createElement("span")
			span.innerText = content
			pre.appendChild(span)
			pre.addEventListener("click", this.clickLine.bind(this, view, i))
			
			rootView.appendChild(view);
		}
	}
	
	public view(): Vnode<any, any> {
		return <div>
			{(this.warningLines.length != 0 || this.errorLines.length != 0) &&
				<div class="errorReportInfo smallText">
					<div class="clickable" onclick={this.clickLineCategory.bind(this, this.logLines)}>
						<span>Logs:&nbsp;</span>
						<span>{this.logLines.length}</span>
					</div>
					
					<div class="clickable" onclick={this.clickLineCategory.bind(this, this.warningLines)}>
						<span>Warnings:&nbsp;</span>
						<span style="color: orange">{this.warningLines.length}</span>
					</div>
					
					<div class="clickable" onclick={this.clickLineCategory.bind(this, this.errorLines)}>
						<span>Errors:&nbsp;</span>
						<span style="color: red">{this.errorLines.length}</span>
					</div>
				</div>
			}
			
			
			<div class="errorReportHeader"></div>
			<div class="errorReportList"></div>
		</div>
	}
}

export class Content extends SectionContent {
	private readonly report: string
	public static preLoad(section: Section): Promise<any>[] {
		return [
			Requests.loadRaw(`${FILE_ADMIN}?type=GetError&timestamp=${section.getStaticInt("timestamp")}`)
		]
	}
	constructor(section: Section, report: string) {
		super(section)
		this.report = report
	}
	
	public title(): string {
		return Lang.get("errorReports")
	}
	
	private getName(report: ErrorReportInfo): string {
		const date = new Date(report.timestamp).toLocaleString()
		return report.note ? `${report.note} (${date})` : date
	}
	
	public getView(): Vnode<any, any> {
		return m(ErrorReportComponent, {report: this.report})
	}
}