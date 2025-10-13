import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {TitleRow} from "../widgets/TitleRow";
import {DragContainer} from "../widgets/DragContainer";
import {DataStructureInputType} from "../data/DataStructure";
import {ChartData} from "../data/study/ChartData";
import {ArrayInterface} from "../observable/interfaces/ArrayInterface";
import {BtnAdd, BtnCopy, BtnTrash} from "../widgets/BtnWidgets";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}

	public title(): string {
		return Lang.get("create_charts")
	}

	private removeChart(list: ArrayInterface<DataStructureInputType, ChartData>, index: number): void {
		if (!confirm())
			return
		list.remove(index)
		window.location.hash = `${this.sectionData.getHash(this.sectionData.depth)}`
	}
	private addChart(list: ArrayInterface<DataStructureInputType, ChartData>, url: string): void {
		list.push({})
		this.newSection(url)
	}
	private copyChart(list: ArrayInterface<DataStructureInputType, ChartData>, chart: ChartData, index: number, url: string): void {
		list.addCopy(chart, index)
		this.newSection(url)
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const publicCharts = study.publicStatistics.charts
		const personalCharts = study.personalStatistics.charts

		if (!study.questionnaires.get().length)
			return <div class="center spacingTop">{Lang.get("info_no_questionnaires_created")}</div>

		return <div>
			{TitleRow(Lang.getWithColon("charts_public"))}
			<div class="listParent">
				{DragContainer((dragTools) =>
					<div class="listChild">
						{publicCharts.get().map((chart, index) =>
							dragTools.getDragTarget(index, publicCharts,
								<div class="verticalPadding">
									{dragTools.getDragStarter(index, publicCharts)}
									{BtnTrash(this.removeChart.bind(this, publicCharts, index))}
									{BtnCopy(this.copyChart.bind(this, publicCharts, chart, index, `chartEdit:public,chartI:${publicCharts.get().length}`))}
									<a href={this.getUrl(`chartEdit:public,chartI:${index}`)}>
										<span>{chart.title.get() !== "" ? chart.title.get() : Lang.get("unnamed_chart")}</span>
									</a>
								</div>
							)
						)}
					</div>
				)}
				<br />
				{BtnAdd(this.addChart.bind(this, publicCharts, `chartEdit:public,chartI:${publicCharts.get().length}`), Lang.get("add"))}
			</div>

			{TitleRow(Lang.getWithColon("charts_personal"))}
			<div class="listParent">
				{DragContainer((dragTools) =>
					<div class="listChild">
						{personalCharts.get().map((chart, index) =>
							dragTools.getDragTarget(index, personalCharts,
								<div class="verticalPadding">
									{dragTools.getDragStarter(index, personalCharts)}
									{BtnTrash(this.removeChart.bind(this, personalCharts, index))}
									{BtnCopy(this.copyChart.bind(this, personalCharts, chart, index, `chartEdit:personal,chartI:${publicCharts.get().length}`))}
									<a href={this.getUrl(`chartEdit:personal,chartI:${index}`)}>
										<span>{chart.title.get() !== "" ? chart.title.get() : Lang.get("unnamed_chart")}</span>
									</a>
								</div>
							)
						)}
					</div>
				)}
				<br />
				{BtnAdd(this.addChart.bind(this, personalCharts, `chartEdit:personal,chartI:${personalCharts.get().length}`), Lang.get("add"))}
			</div>
		</div>
	}
}