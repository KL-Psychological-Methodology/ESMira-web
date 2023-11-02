import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Section} from "../site/Section";
import {Requests} from "../singletons/Requests";
import {FILE_STATISTICS} from "../constants/urls";
import {Study} from "../data/study/Study";
import {ChartView} from "../widgets/ChartView";
import {ObservablePromise} from "../observable/ObservablePromise";
import {LoadedStatistics} from "../loader/csv/CsvLoaderCollectionFromCharts";

export class Content extends SectionContent {
	private readonly publicStatisticsPromises: ObservablePromise<LoadedStatistics>[]
	
	public static preLoad(section: Section): Promise<any>[] {
		return [ section.getStudyPromise() ]
	}
	constructor(section: Section, study: Study) {
		super(section)
		
		const promise = this.loadPublicStatistics()
		this.publicStatisticsPromises = study.publicStatistics.charts.get().map(
			(_chart, index) => new ObservablePromise<LoadedStatistics>(promise, null, `publicChart${index}`)
		)
	}
	
	public title(): string {
		return this.getStudyOrThrow().title.get()
	}
	
	private async loadPublicStatistics(): Promise<LoadedStatistics> {
		const study = this.getStudyOrNull()
		let accessKey: string
		if(study)
			accessKey = study.accessKeys.get().length ? study.accessKeys.get()[0].get() : ""
		else
			accessKey = this.getDynamic("accessKey", "").get()
		
		try {
			const publicStatistics = await Requests.loadJson(
				FILE_STATISTICS
					.replace("%d", this.getStaticInt("id")?.toString() ?? "-1")
					.replace("%s", accessKey)
			)
			return { mainStatistics: publicStatistics }
		}
		catch(e: any) {
			this.section.loader.error(e.message || e)
			return { mainStatistics: {} }
		}
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			{
				study.publicStatistics.charts.get().map((chartData, index) => {
					return ChartView(chartData, this.publicStatisticsPromises[index])
				})
			}
		</div>
	}
}