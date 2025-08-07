import { ObservableStructure, ObservableStructureDataType } from "../../observable/ObservableStructure";
import { Statistics } from "./Statistics";
import { Questionnaire } from "./Questionnaire";
import { ObservableTypes } from "../../observable/types/ObservableTypes";
import { EventUploadSettings } from "./EventUploadSettings";
import { BaseObservable } from "../../observable/BaseObservable";
import { TranslationRootInterface } from "../../observable/interfaces/TranslationRootInterface";
import { Input, InputMediaTypes } from "./Input";
import { RepairStudy } from "../../helpers/RepairStudy";
import { Lang } from "../../singletons/Lang";

export class Study extends ObservableStructure implements TranslationRootInterface {
	public lastChanged: number

	public id = this.primitive<number>("id", 0)
	public version = this.primitive<number>("version", 0)
	public subVersion = this.primitive<number>("subVersion", 0)
	public packageVersion = this.primitive<string>("packageVersion", "0.0.0")
	public serverVersion = this.primitive<number>("serverVersion", 0)
	public lang = this.primitive<string>("lang", "")
	public newChanges = this.primitive<boolean>("new_changes", false)
	public published = this.primitive<boolean>("published", false)
	public publishedWeb = this.primitive<boolean>("publishedWeb", true)
	public publishedAndroid = this.primitive<boolean>("publishedAndroid", true)
	public publishedIOS = this.primitive<boolean>("publishedIOS", true)
	public studyOver = this.primitive<boolean>("studyOver", false)
	public sendMessagesAllowed = this.primitive<boolean>("sendMessagesAllowed", true)
	public randomGroups = this.primitive<number>("randomGroups", 1)
	public enableRewardSystem = this.primitive<boolean>("enableRewardSystem", false)
	public rewardVisibleAfterDays = this.primitive<number>("rewardVisibleAfterDays", 0)
	public defaultLang = this.primitive<string>("defaultLang", "en")
	public useFallback = this.primitive<boolean>("useFallback", true)

	public accessKeys = this.primitiveArray<string>("accessKeys")
	public langCodes = this.primitiveArray<string>("langCodes", ["en"])

	public title = this.translatable("title", "Study title")
	public studyDescription = this.translatable("studyDescription", "")
	public studyTag = this.translatable("studyTag", "")
	public informedConsentForm = this.translatable("informedConsentForm", "")
	public postInstallInstructions = this.translatable("postInstallInstructions", "")
	public chooseUsernameInstructions = this.translatable("chooseUsernameInstructions", "")
	public webQuestionnaireCompletedInstructions = this.translatable("webQuestionnaireCompletedInstructions", "")
	public webInstallInstructions = this.translatable("webInstallInstructions", "")
	public contactEmail = this.translatable("contactEmail", "")
	public rewardEmailContent = this.translatable("rewardEmailContent", "")
	public rewardInstructions = this.translatable("rewardInstructions", "")
	public postStudyNote = this.translatable("postStudyNote", "")

	public questionnaires = this.objectArray("questionnaires", Questionnaire)

	public eventUploadSettings = this.object("eventUploadSettings", EventUploadSettings)
	public publicStatistics = this.object("publicStatistics", Statistics)
	public personalStatistics = this.object("personalStatistics", Statistics)

	constructor(data: ObservableStructureDataType, parent: BaseObservable<ObservableTypes> | null, lastChanged: number, repair: RepairStudy | null) {
		if (repair != null && !repair.repairStudy(data))
			throw Lang.get("error_study_not_compatible", data["title"]?.toString() ?? "Error")

		let defaultLang = data["defaultLang"] as string ?? "en"
		if (data.hasOwnProperty("langCodes") && (data["langCodes"] as string[]).indexOf(defaultLang) == -1)
			defaultLang = (data["langCodes"] as string[])[0]

		super(data, parent, data["id"] as string ?? "-1", defaultLang)
		this.lastChanged = lastChanged
	}

	public updateKeyName(_keyName: string, parent?: BaseObservable<ObservableTypes>) {
		super.updateKeyName(this.id.get().toString(), parent)
	}

	/**
	 * Executes the callback function for each input element.
	 * If callback function returns false, the loop is canceled
	 */
	public forEachInput(callback: (input: Input, index: number) => void | boolean) {
		let questionnaires = this.questionnaires.get();
		let index = 0
		for (let iQ = questionnaires.length - 1; iQ >= 0; iQ--) {
			let pages = questionnaires[iQ].pages.get();
			for (let iP = pages.length - 1; iP >= 0; iP--) {
				let inputs = pages[iP].inputs.get();
				for (let iI = inputs.length - 1; iI >= 0; iI--) {
					if (callback(inputs[iI], index++) === false)
						return;
				}
			}
		}
	}

	public hasMedia(): boolean {
		let hasMedia = false
		this.forEachInput((input) => {
			if (input.getMediaType() != null) {
				hasMedia = true
				return false
			}
		})
		return hasMedia
	}

	public hasMerlinScripts(): boolean {
		return this.questionnaires.get().some((questionnaire) => {
			questionnaire.endScriptBlock.get() !== "" || questionnaire.pages.get().some((page) => {
				page.relevance.get() !== "" || page.inputs.get().some((input) => {
					input.relevance.get() !== "" || input.textScript.get() !== ""
				})
			})
		})
	}

	public getInputNamesPerType(): Record<InputMediaTypes, string[]> {
		const names: Record<string, string[]> = {}
		this.forEachInput((input) => {
			const type = input.getMediaType()
			if (type != null) {
				if (!names.hasOwnProperty(type))
					names[type] = []
				names[type].push(input.name.get());
			}
		});

		return names;
	}
}