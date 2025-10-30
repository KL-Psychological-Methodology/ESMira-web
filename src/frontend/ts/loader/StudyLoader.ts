import {Study} from "../data/study/Study";
import {PluginFrontendInstructions} from "../plugin/PluginInterfaces";
import m from "mithril";
import {PromiseCache} from "../singletons/PromiseCache";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN, FILE_STUDIES} from "../constants/urls";
import {Lang} from "../singletons/Lang";
import {Questionnaire} from "../data/study/Questionnaire";
import {JsonTypes} from "../observable/types/JsonTypes";
import {ObserverId} from "../observable/BaseObservable";
import {RepairStudy} from "../helpers/RepairStudy";
import {Page} from "../data/study/Page";
import {createUniqueName} from "../helpers/UniqueName";
import {DataStructureInputType} from "../data/DataStructure";
import {TranslatableObject} from "../observable/TranslatableObject";

export type StudiesDataType = TranslatableObject<Study>

export interface StudyMetadata {
	owner: string
	lastSavedBy: string,
	lastSavedAt: number,
	createdTimestamp: number
}
export interface StudyDataFromServer extends StudyMetadata {
	id: number,
	[key: string]: JsonTypes
}

/**
 * Holds all available study information.
 * Be aware that study objects can be removed ore recreated at any point.
 * They should not be cached (or, if they are, an observer needs to make sure they are reloaded)
 * Note, that because of how {@link BaseObservable} work, all observers are stored in {@link studyCache} which means, when a study gets replaced, its observer will persist
 */
export class StudyLoader {
	/**
	 * Stores all studies.
	 * Because all studies are saved in the same observable, all of their shared storage is connected (Explanation of what that means in {@link BaseObservable}).
	 * When saving a study, the study observable is replaced with a new observable containing the study returned from the backend.
	 * But because the old and new study observable both share the same parent through {@link studyCache}, they will behave exactly the same.
	 * @see Code documentation in {@link BaseObservable}
	 */
	private readonly studyCache = new TranslatableObject<Study>(null, "studies")
	private readonly questionnaireRegister: Record<number, number> = {}
	private readonly observerIds: Record<number, ObserverId> = {}
	private readonly serverVersion: number
	private readonly packageVersion: string
	private readonly repair: RepairStudy
	private readonly plugins: Record<string, PluginFrontendInstructions>
	public readonly ownerRegister: Record<string, number[]> = {}
	public readonly studyMetadata: Record<number, StudyMetadata> = {}
	
	constructor(serverVersion: number, packageVersion: string, plugins: Record<string, PluginFrontendInstructions>) {
		this.serverVersion = serverVersion
		this.packageVersion = packageVersion
		this.repair = new RepairStudy(serverVersion, packageVersion)
		this.plugins = plugins
		
		this.studyCache.addObserver(() => {
			m.redraw() //redraw is asynchronous, so this should be executed after all other observers
		})
	}
	
	private updateMetadata(studyId: number, metadata: StudyMetadata) {
		const ownerName = metadata.hasOwnProperty("owner") ? metadata.owner.toString() : ""
		
		this.studyMetadata[studyId] = {
			owner: ownerName,
			lastSavedBy: metadata.lastSavedBy ?? "",
			lastSavedAt: metadata.hasOwnProperty("lastSavedAt")
				? parseInt(metadata.lastSavedAt.toString()) //server sends the wrong datatype?
				: Math.round(Date.now() / 1000),
			createdTimestamp: metadata.hasOwnProperty("createdTimestamp")
				? parseInt(metadata.createdTimestamp.toString()) //server sends the wrong datatype?
				: Math.round(Date.now() / 1000)
		}
		if(ownerName) {
			if (!this.ownerRegister.hasOwnProperty(ownerName))
				this.ownerRegister[ownerName] = [studyId]
			else
				this.ownerRegister[ownerName].push(studyId)
		}
	}
	private removeFromMetadata(study: Study) {
		const studyId = study.id.get()
		const ownerName = this.studyMetadata[studyId]?.owner
		
		if(ownerName) {
			const ownerEntry = this.ownerRegister[ownerName]
			if(ownerEntry.length) {
				let index = 0
				for (const entry of ownerEntry) {
					if (entry == studyId) {
						this.ownerRegister[ownerName].splice(index, 1)
						break
					}
					++index
				}
			}
			else
				delete this.ownerRegister[ownerName]
			
			delete this.studyMetadata[studyId]
		}
	}
	
	private addPluginStructureToStudy(study: Study) {
		for(const pluginName in this.plugins) {
			const dataStructure = this.plugins[pluginName].studyJsonDataStructure
			if(!dataStructure) {
				continue
			}
			
			study.pluginData.addPluginData(pluginName, dataStructure);
		}
	}
	
	public loadStrippedStudyList(): Promise<StudiesDataType> {
		return PromiseCache.get("strippedStudies", async () => {
			PromiseCache.remove("availableStudies")
			const studiesJson: StudyDataFromServer[] = await Requests.loadJson(`${FILE_ADMIN}?type=GetStrippedStudyList`)
			
			for(const studyData of studiesJson) {
				const id = studyData.id
				const study = new Study(studyData, this.studyCache, Math.round(Date.now() / 1000), null)
				
				if(!this.studyCache.contains(id)) {
					this.studyCache.insert(id, study, undefined, true)
				}
				this.updateMetadata(id, studyData)
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
				this.studyCache.empty(true)
				throw new Error(Lang.get("error_wrong_accessKey"))
			}
			
			studiesJson.forEach((studyData: Record<string, any>) => {
				try {
					const id = studyData["id"]
					const study = new Study(studyData, this.studyCache, Date.now(), this.repair)
					
					this.studyCache.insert(id, study, undefined, true)
					
					for(const questionnaire of study.questionnaires.get()) {
						this.questionnaireRegister[questionnaire.internalId.get()] = id
					}
				}
				catch(e: any) {
					console.error(e.message || e)
				}
			})
			
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
			} = await Requests.loadJson(`${FILE_ADMIN}?type=GetFullStudy&study_id=${id}`)
			
			const study = new Study(studyData.config, this.studyCache, lastChanged, this.repair)
			this.addPluginStructureToStudy(study)
			for(const langCode in studyData.languages) {
				study.addLanguage(langCode, studyData.languages[langCode])
			}
			
			this.studyCache.insert(id, study, undefined, true)
			
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
			
			for(const questionnaire of study.questionnaires.get()) {
				this.questionnaireRegister[questionnaire.internalId.get()] = id
			}
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
	
	public async getStudyIdFromQuestionnaireId(qId: number): Promise<number> {
		if(this.questionnaireRegister.hasOwnProperty(qId))
			return this.questionnaireRegister[qId]
		else {
			const [studyId] = await Requests.loadJson(`${FILE_ADMIN}?type=GetStudyFromQuestionnaireId&qId=${qId}`)
			this.questionnaireRegister[qId] = studyId
			return studyId
		}
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
	
	public async addStudy(studyData: DataStructureInputType): Promise<number | null> {
		const id: number = await Requests.loadJson(`${FILE_ADMIN}?type=GetNewId&for=study&study_id=-1`)
		
		studyData.id = id
		studyData.serverVersion = this.serverVersion
		studyData.packageVersion = this.packageVersion
		
		this.updateMetadata(id, {} as StudyMetadata)
		
		const study = new Study(studyData, this.studyCache, Date.now(), this.repair)
		this.studyCache.insert(id, study, undefined, true)
		PromiseCache.save(`study${id}`, Promise.resolve(study)) // studies are loaded through the PromiseCache
		study.setIsDifferent(true)
		
		return id
	}
	
	public updateStudy(oldStudy: Study, newStudy: Study): Study {
		const id = oldStudy.id.get()
		this.studyCache.insert(id, newStudy, undefined, true)
		return newStudy
	}
	public updateStudyJson(study: Study, data: DataStructureInputType): Study {
		const id = study.id.get()
		data[id] = id
		const newStudy = new Study(data, this.studyCache, study.lastChanged, this.repair)
		this.addPluginStructureToStudy(newStudy)
		this.studyCache.insert(id, newStudy, undefined, true)
		return newStudy
	}
	
	public async deleteStudy(study: Study): Promise<void> {
		const id = study.id.get()
		
		let response = id
		if(study.version.get() > 0)
			response = await Requests.loadJson(`${FILE_ADMIN}?type=DeleteStudy`, "post", `study_id=${id}`)
		
		if(response != id)
			throw new Error(Lang.get("error_unknown"))
		
		study.removeConnectedObservers()
		
		this.removeFromMetadata(study)
		
		//make sure the study is removed after the page got a chance to clear. If not, several getStudy() calls would cause exceptions!
		window.setTimeout(() => {
			this.studyCache.remove(id, true)
		}, 500)
	}
	
	public async addQuestionnaire(study: Study, questionnaireData: DataStructureInputType): Promise<Questionnaire> {
		const questionnaires = study.questionnaires.get()
		const filtered = []
		for(const questionnaire of questionnaires) {
			filtered.push(questionnaire.internalId.get())
		}
		
		questionnaireData["internalId"] = await Requests.loadJson(FILE_ADMIN + "?type=GetNewId&for=questionnaire&study_id=" + study.id.get(), "post", JSON.stringify(filtered))
		const newQuestionnaire = study.questionnaires.push(questionnaireData)
		this.autoValidateQuestionnaire(study, newQuestionnaire)
		return newQuestionnaire
	}
	
	public autoValidateQuestionnaire(study: Study, questionnaire: Questionnaire): void {
		for(const page of questionnaire.pages.get()) {
			this.autoValidatePage(study, page)
		}
	}
	public autoValidatePage(study: Study, page: Page): void {
		for(const input of page.inputs.get()) {
			const newName = createUniqueName(study, input.name.get(), (oldName) => {
				const match = oldName.match(/(.+)_(\d+)$/)
				if(match == null)
					return oldName + "_2"
				else
					return `${match[1]}_${parseInt(match[2])+1}`
			})
			if(newName == null)
				throw new Error(`Could not rename ${input.name.get()}. Reverting copy!`)
			
			input.name.set(newName)
		}
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
		const response = await Requests.loadJson(
			`${FILE_ADMIN}?type=${saveType}&study_id=${study.id.get()}&lastChanged=${study.lastChanged}`,
			"post",
			JSON.stringify(studies)
		) as { metaData: StudyMetadata, json: Record<string, DataStructureInputType> }
		
		study.lastChanged = response.metaData.lastSavedAt
		this.updateMetadata(study.id.get(), response.metaData)
		
		//just in case the server changed something important in the json, we reload the study:
		study.currentLangCode.set(study.defaultLang.get())
		const newStudy = this.updateStudyJson(study, response.json["_"])
		for(let langCode in response.json) {
			if(langCode != "_")
				newStudy.addLanguage(langCode, response.json[langCode])
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