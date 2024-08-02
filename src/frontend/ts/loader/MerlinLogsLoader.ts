import {LoginDataInterface} from "../admin/LoginDataInterface";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";

export class MerlinLogsLoader {
	public readonly studiesWithNewMerlinLogsCount = new ObservablePrimitive<number>(0, null, "studiesWithNewMerlinLogsCount")
	public readonly studiesWithNewMerlinLogsList: Record<number, boolean> = {}
	
	constructor(data: LoginDataInterface) {
		if(data.newMerlinLogs) {
			for(let id of data.newMerlinLogs) {
				this.studiesWithNewMerlinLogsList[id] = true
			}
			this.studiesWithNewMerlinLogsCount.set(data.newMerlinLogs.length)
		}
	}
	
	public setStudyNewLogsRemaining(studyId: number, logsRemaining: boolean) {
		this.studiesWithNewMerlinLogsList[studyId] = logsRemaining
		this.studiesWithNewMerlinLogsCount.set(
			Object.values(this.studiesWithNewMerlinLogsList).filter((value) => value).length
		)
	}
}