import {ObservableRecord} from "../observable/ObservableRecord";
import {Study} from "../data/study/Study";
import m from "mithril";
import {PromiseCache} from "../singletons/PromiseCache";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN, FILE_STUDIES} from "../constants/urls";
import {Lang} from "../singletons/Lang";
import {TranslatableObjectDataType} from "../observable/TranslatableObject";
import {Questionnaire} from "../data/study/Questionnaire";
import {JsonTypes} from "../observable/types/JsonTypes";
import {ObserverId} from "../observable/BaseObservable";
import {RepairStudy} from "../helpers/RepairStudy";

export type StudiesDataType = ObservableRecord<Study>

export class StudyLoader {
	private readonly studyCache = new ObservableRecord<Study>({}, "studies")
	private readonly observerIds: Record<number, ObserverId> = {}
	private readonly serverVersion: number
	private readonly packageVersion: string
	private readonly repair: RepairStudy
	
	constructor(serverVersion: number, packageVersion: string) {
		this.serverVersion = serverVersion
		this.packageVersion = packageVersion
		this.repair = new RepairStudy(serverVersion, packageVersion)
		
		this.studyCache.addObserver(() => {
			m.redraw() //redraw is asynchronous, so this should be executed after all other observers
		})
	}
	
	public loadStrippedStudyList(): Promise<StudiesDataType> {
		return PromiseCache.get("strippedStudies", async () => {
			PromiseCache.remove("availableStudies")
			const studiesJson: Record<string, any>[] = await Requests.loadJson(`${FILE_ADMIN}?type=GetStrippedStudyList`)
			
			for(let studyData of studiesJson) {
				const id = studyData["id"]
				const study = new Study(studyData, this.studyCache, Math.round(Date.now() / 1000), null)
				if(!this.studyCache.exists(id))
					this.studyCache.add(id, study)
			}
			
			return this.studyCache
		})
	}
	
	public async loadAvailableStudies(accessKey: string, reload: boolean = false): Promise<StudiesDataType> {
		if(reload)
			PromiseCache.remove("availableStudies")
		
		return PromiseCache.get(`availableStudies`, async () => {
			PromiseCache.remove("strippedStudies")
			const studiesJson: Record<string, any>[] = await Requests.loadJson(`${FILE_STUDIES}?access_key=${accessKey}`)
			if(!studiesJson.length) {
				this.studyCache.set({})
				throw new Error(Lang.get("error_wrong_accessKey"))
			}
			
			const filteredStudies: Record<number, Study> = {}
			studiesJson.forEach((studyData: Record<string, any>) => {
				try {
					const id = studyData["id"]
					const study = new Study(studyData, this.studyCache, Date.now(), this.repair)
					filteredStudies[id] = study
					if(this.studyCache.exists(id))
						this.studyCache.remove(id) //remove stripped study
					this.studyCache.add(id, study)
				}
				catch(e: any) {
					console.error(e.message || e)
				}
			})
			
			this.studyCache.set(filteredStudies)
			return this.studyCache
		})
	}
	
	public removeStudyFromCache(id: number): void {
		PromiseCache.remove(`study${id}`)
	}
	
	public async loadFullStudy(id: number, lastChanged: number = Math.round(Date.now() / 1000)): Promise<Study> {
		return PromiseCache.get(`study${id}`, async () => {
			const studyData: {
				config: Record<string, any>,
				languages: Record<string, any>
			} = await Requests.loadJson(`${FILE_ADMIN}?type=full_study&study_id=${id}`)
			
			const study = new Study(studyData.config, this.studyCache, lastChanged, this.repair)
			for(const langCode in studyData.languages) {
				study.addLanguage(langCode, studyData.languages[langCode])
			}
			
			if(this.studyCache.exists(id))
				this.studyCache.remove(id) //remove stripped study
			
			this.studyCache.add(id, study)
			
			this.observerIds[id] = study.addObserver(async (_obs, turnedDifferent) => {
				const currentStudy = this.studyCache.get()[id]
				if(currentStudy && turnedDifferent) {
					const lastChangedServer = await Requests.loadJson(`${FILE_ADMIN}?type=CheckChanged&study_id=${id}`)
					
					if(lastChangedServer > currentStudy.lastChanged) {
						this.removeStudyFromCache(id)
						const newStudy = await this.loadFullStudy(id, lastChangedServer)
						this.updateStudy(currentStudy, newStudy)
						alert(Lang.get("error_study_was_changed", currentStudy.title.get()));
					}
				}
			}, this.observerIds[id])
			
			
			return study
		})
	}
	
	public hasUnsavedStudy(): boolean {
		const studies = this.studyCache.get()
		for(let studyId in studies) {
			if(studies[studyId].isDifferent())
				return true
		}
		return false
	}
	
	public getStudies(): StudiesDataType {
		return this.studyCache
	}
	public getSortedStudyList(studyList: Study[] = Object.values(this.studyCache.get())): Study[] {
		//sort studies by name:
		studyList.sort((studyA: Study, studyB: Study) => {
			const titleA = studyA.title.get().toLowerCase()
			const titleB = studyB.title.get().toLowerCase()
			if(titleA > titleB)
				return 1
			else if(titleA < titleB)
				return -1
			else
				return 0
		});
		return studyList
	}
	
	public async addStudy(studyData: TranslatableObjectDataType): Promise<number | null> {
		const id: number = await Requests.loadJson(`${FILE_ADMIN}?type=GetNewId&for=study&study_id=-1`)
		
		studyData["id"] = id
		studyData["serverVersion"] = this.serverVersion
		studyData["packageVersion"] = this.packageVersion
		
		const study = new Study(studyData, this.studyCache, Date.now(), this.repair)
		this.studyCache.add(id, study)
		PromiseCache.save(`study${id}`, Promise.resolve(study))
		study.setDifferent(true)
		return id
	}
	
	public updateStudy(oldStudy: Study, newStudy: Study): Study {
		const id = oldStudy.id.get()
		this.studyCache.remove(id)
		this.studyCache.add(id, newStudy)
		return newStudy
	}
	public updateStudyJson(study: Study, data: TranslatableObjectDataType): Study {
		const id = study.id.get()
		this.studyCache.remove(id)
		data[id] = id
		const newStudy = new Study(data, this.studyCache, study.lastChanged, this.repair)
		this.studyCache.add(id, newStudy)
		return newStudy
	}
	
	public async deleteStudy(study: Study): Promise<void> {
		const id = study.id.get()
		
		let response = id
		if(study.version.get() > 0)
			response = await Requests.loadJson(`${FILE_ADMIN}?type=DeleteStudy`, "post", `study_id=${id}`)
		
		if(response != id)
			throw new Error(Lang.get("error_unknown"))
		
		study.removeAllConnectedObservers()
		
		//make sure the study is removed after the page got a chance to clear. If not, several getStudy() calls would cause exceptions!
		window.setTimeout(() => {
			this.studyCache.remove(id)
		}, 500)
	}
	
	public async addQuestionnaire(study: Study, questionnaireData: TranslatableObjectDataType): Promise<Questionnaire> {
		const questionnaires = study.questionnaires.get()
		const filtered = []
		for(let questionnaire of questionnaires) {
			filtered.push(questionnaire.internalId.get())
		}
		
		questionnaireData["internalId"] = await Requests.loadJson(FILE_ADMIN + "?type=GetNewId&for=questionnaire&study_id=" + study.id.get(), "post", JSON.stringify(filtered))
		return study.questionnaires.push(questionnaireData)
	}
	
	public async saveStudy(study: Study): Promise<void> {
		const defaultLang = study.defaultLang.get();
		const studies: Record<string, JsonTypes> = {};
		const currentLang = study.currentLangCode.get()
		for(const langCode of study.langCodes.get()) {
			study.currentLangCode.set(langCode.get())
			const langJson = study.createJson({dontIncludeAllLanguages: true})
			langJson["lang"] = langCode.get()
			studies[langCode.get() == defaultLang ? "_" : langCode.get()] = langJson
		}
		
		const saveType = study.version.get() == 0 ? "CreateStudy" : "SaveStudy";
		const {lastChanged, json} = await Requests.loadJson(
			`${FILE_ADMIN}?type=${saveType}&study_id=${study.id.get()}&lastChanged=${study.lastChanged}`,
			"post",
			JSON.stringify(studies)
		)
		study.lastChanged = lastChanged

		//just in case the server changed something important in the json, we reload the study:
		study.currentLangCode.set(study.defaultLang.get())
		const newStudy = this.updateStudyJson(study, json["_"])
		for(let langCode in json) {
			if(langCode != "_")
				newStudy.addLanguage(langCode, json[langCode])
		}
		newStudy.currentLangCode.set(currentLang)
		newStudy.hasMutated()
		m.redraw()
	}
	
	public async publishStudy(study: Study): Promise<void> {
		const studyId = study.id.get()
		
		const { lastChanged } = await Requests.loadJson(`${FILE_ADMIN}?type=MarkStudyAsUpdated`, "post", `study_id=${studyId}`)
		study.lastChanged = lastChanged
		study.version.set(study.version.get() + 1, true)
		study.subVersion.set(0, true)
		study.newChanges.set(false, true)
		study.hasMutated()
	}
}