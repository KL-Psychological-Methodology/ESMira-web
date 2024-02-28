import m, {Vnode} from "mithril";
import {Input, InputResponseType} from "../data/study/Input";
import {Study} from "../data/study/Study";
import {Lang} from "../singletons/Lang";
import {BindObservable} from "../widgets/BindObservable";
import {ObservableLangChooser} from "../widgets/ObservableLangChooser";
import {DragContainer} from "../widgets/DragContainer";
import {DashRow} from "../widgets/DashRow";
import {RichText} from "../widgets/RichText";
import {PrimitiveType} from "../observable/types/PrimitiveType";
import {DashElement} from "../widgets/DashElement";
import {BaseObservable} from "../observable/BaseObservable";
import {PossibleDevices} from "../widgets/NotCompatibleIcon";
import {BtnAdd, BtnTrash} from "../widgets/BtnWidgets";


const InputCategories = {
	"passive": Lang.get("input_category_passive"),
	"classic": Lang.get("input_category_classic"),
	"special": Lang.get("input_category_special"),
	"sensor": Lang.get("input_category_sensor"),
	"media": Lang.get("input_category_media"),
}

type InputType = Record<InputResponseType, InputEntry>


export interface InputEntry {
	title: string
	helpUrl: string
	category: keyof typeof InputCategories
	notCompatible?: PossibleDevices[]
	view: () => Vnode<any, any>[]
}

export class InputOptionDesigner {
	public readonly study: Study
	public readonly input: Input
	public readonly getUrl: (name: string) => string
	public readonly goTo: (name: string) => void
	
	
	private readonly inputTypes: InputType = {
		"app_usage": {
			title: Lang.get("input_app_usage"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#App-usage-tracking",
			category: "sensor",
			notCompatible: ["Web", "iOS"],
			view: () => [
				<div>
					{this.requiredOption()}
				</div>,
				<div>
					<label>
						<small>{Lang.get("packageId")}</small>
						<input class="big" type="text" { ... BindObservable(this.input.packageId) }/>
						<small>{Lang.get("packageId_desc")}</small>
					</label>
				</div>
			]
			
		},
		"binary": {
			title: Lang.get("input_binary"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Binary-item",
			category: "classic",
			view: () => [
				<div>
					{this.requiredOption()}
				</div>,
				this.defaultValueOption(),
				this.leftRightLabelOption()
			]
		},
		"bluetooth_devices": {
			title: Lang.get("input_bluetooth_devices"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#List-of-bluetooth-devices",
			category: "sensor",
			notCompatible: ["Web"],
			view: () => this.onlyRequiredAndDefaultOptions()
		},
		"compass": {
			title: Lang.get("input_compass"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Compass-item",
			category: "sensor",
			notCompatible: ["Web"],
			view: () => [
				<div>
					{this.requiredOption()}
					{this.showValueOption()}
					{this.checkedOptionElement(this.input.numberHasDecimal, Lang.get("allow_decimal_input"))}
				</div>,
				this.defaultValueOption()
			]
		},
		"countdown": {
			title: Lang.get("input_countdown"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Countdown-item",
			category: "special",
			notCompatible: ["Web"],
			view: () => [
				<div>
					{ this.requiredOption() }
				</div>,
				<div>
					<label class="vertical noDesc">
						<small>{Lang.get("timeout")}</small>
						<input type="text" { ... BindObservable(this.input.timeoutSec) }/>
						<small>{Lang.get("seconds")}</small>
					</label>
					<label class="vertical noDesc">
						<input type="checkbox" { ... BindObservable(this.input.playSound) }/>
						<span>{Lang.get("play_sound_when_finished")}</span>
					</label>
				</div>
			]
		},
		"date": {
			title: Lang.get("input_date"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Date-input",
			category: "classic",
			view: () => this.onlyRequiredAndDefaultOptions()
		},
		"dynamic_input": {
			title: Lang.get("input_dynamic_input"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Dynamic-item",
			category: "special",
			view: () => {
				const removeSubInput = (index: number) => {
					this.input.subInputs.remove(index)
				}
				const addSubInput = () => {
					this.input.subInputs.push({})
					// this.goTo()
				}
				
				return [
					<label class="vertical noTitle noDesc">
						<input type="checkbox" {...BindObservable(this.input.random)}/>
						<span>{Lang.get("random")}</span>
					</label>,
					<div class="stretched">
						<h2>{Lang.getWithColon("choices")}</h2>{
						DragContainer((dragTools) => {
							return <div class="listParent">
								<div class="listChild">
									{
										this.input.subInputs.get().map((subInput, index) => {
											return dragTools.getDragTarget(index, this.input.subInputs,
												<div class="vertical">
													{dragTools.getDragStarter(index, this.input.subInputs)}
													{BtnTrash(removeSubInput.bind(this, index))}
													<a href={this.getUrl(`inputEdit,subInput:${index}`)}>
														{`${index}: ${Lang.getDynamic("input_" + subInput.responseType.get())}${subInput.required.get() ? "*" : ""}`}
													</a>
												</div>
											)
										})
									}
									<div class="spacingTop center">
										{BtnAdd(addSubInput.bind(this), Lang.get("add"))}
									</div>
								</div>
							</div>
						})
					}</div>
					
				]
			}
		},
		"file_upload": {
			title: Lang.get("input_file_upload"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Image-upload",
			category: "media",
			view: () => this.onlyRequiredAndDefaultOptions()
		},
		"image": {
			title: Lang.get("input_image"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Image",
			category: "passive",
			view: () =>
				[ this.urlOption() ]
			
		},
		"likert": {
			title: Lang.get("input_likert"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Likert-scale",
			category: "classic",
			view: () => [
				<div>
					{this.requiredOption()}
					{this.showValueOption()}
				</div>,
				<div>
					{this.inputOptionElement(this.input.likertSteps, Lang.get("likert_number_of_points"))}
				</div>,
				this.leftRightLabelOption()
			]
		},
		"list_multiple": {
			title: Lang.get("input_list_multiple"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#List-multiple-choice",
			category: "classic",
			view: () => [
				this.requiredOption(),
				this.defaultValueOption(),
				this.itemsOption()
			]
		},
		"list_single": {
			title: Lang.get("input_list_single"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#List-single-choice",
			category: "classic",
			view: () => [
				<div>
					{this.requiredOption()}
					{this.checkedOptionElement(this.input.asDropDown, Lang.get("show_as_dropdown"))}
					{this.checkedOptionElement(this.input.forceInt, Lang.get("save_as_number"))}
				</div>,
				this.defaultValueOption(),
				this.itemsOption()
			]
		},
		"location": {
			title: Lang.get("input_location"),
			helpUrl: "",
			category: "classic",
			view: () => [
				<div>
					{this.requiredOption()}
				</div>,
				<div>
					{this.inputOptionElement(this.input.resolution, Lang.get("input_location_resolution"))}
				</div>
			]
		},
		"number": {
			title: Lang.get("input_number"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Number-input",
			category: "classic",
			view: () => [
				<div>
					{this.requiredOption()}
					{this.checkedOptionElement(this.input.numberHasDecimal, Lang.get("allow_decimal_input"))}
				</div>,
				this.defaultValueOption()
			]
		},
		"photo": {
			title: Lang.get("input_photo"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Take-a-picture",
			category: "media",
			view: () => [ this.requiredOption() ]
		},
		"record_audio": {
			title: Lang.get("input_record_audio"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Record-audio",
			category: "media",
			notCompatible: ["Web"],
			view: () => this.onlyRequiredAndDefaultOptions()
		},
		"share": {
			title: Lang.get("input_share"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Share-item",
			category: "special",
			view: () => [
				this.requiredOption(),
				this.urlOption()
			]
		},
		"text": {
			title: Lang.get("input_text"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Simple-text-view",
			category: "passive",
			view: () => []
		},
		"text_input": {
			title: Lang.get("input_text_input"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Text-input",
			category: "classic",
			view: () => this.onlyRequiredAndDefaultOptions()
		},
		"time": {
			title: Lang.get("input_time"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Time-input",
			category: "classic",
			view: () => [
				<div>
					{this.checkedOptionElement(this.input.forceInt, Lang.get("save_as_minutes"))}
					{this.requiredOption()}
				</div>,
				this.defaultValueOption()
			]
		},
		"va_scale": {
			title: Lang.get("input_va_scale"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Visual-analogue-scale",
			category: "classic",
			view: () => [
				<div>
					{this.requiredOption()}
					{this.showValueOption()}
				</div>,
				<div>
					{this.inputOptionElement(this.input.defaultValue, Lang.get("prefilledValue"), "", "number")}
					<label class="spacingTop">
						<small>{Lang.get("max_value")}</small>
						<input type="number" { ... BindObservable(this.input.maxValue) }/>
						<small>{Lang.get("max_value_vas_desc")}</small>
					</label>
				</div>,
				this.leftRightLabelOption()
			]
		},
		"video": {
			title: Lang.get("input_video"),
			helpUrl: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Questionnaire-Items#Video",
			category: "passive",
			view: () => [
				this.requiredOption(),
				this.urlOption()
			]
		}
	}
	private readonly sortedInputTypeKeys: InputResponseType[]
	
	constructor(study: Study, input: Input, getUrl: (name: string) => string, goTo: (name: string) => void) {
		this.study = study
		this.input = input
		this.getUrl = getUrl
		this.goTo = goTo
		
		const inputTypeKeys = Object.keys(this.inputTypes) as InputResponseType[]
		inputTypeKeys.sort((a, b) => {
			const titleA = this.inputTypes[a].title
			const titleB = this.inputTypes[b].title
			if(titleA == titleB)
				return 0
			else
				return titleA > titleB ? 1 : -1
		})
		this.sortedInputTypeKeys = inputTypeKeys
	}
	
	private onlyRequiredAndDefaultOptions(): Vnode<any, any>[] {
		return [
			<div>{this.requiredOption()}</div>,
			this.defaultValueOption()
		]
	}
	
	private inputOptionElement(obs: BaseObservable<PrimitiveType>, title: string, className= "", type = "text"): Vnode<any, any> {
		return <label class="line noDesc">
			<small>{title}</small>
			<input type={type} class={className} { ... BindObservable(obs) }/>
			{ObservableLangChooser(this.study)}
		</label>
	}
	private checkedOptionElement(obs: BaseObservable<PrimitiveType>, title: string): Vnode<any, any> {
		return <label class="line noTitle noDesc">
			<input type="checkbox" { ... BindObservable(obs) }/>
			<span>{title}</span>
		</label>
	}
	
	private urlOption(): Vnode<any, any> {
		return <div class="stretched">
			{this.inputOptionElement(this.input.url, Lang.get("url"), "big", "url")}
		</div>
	}
	private requiredOption(): Vnode<any, any> {
		return this.checkedOptionElement(this.input.required, Lang.get("required_desc"))
	}
	private showValueOption(): Vnode<any, any> {
		return this.checkedOptionElement(this.input.showValue, Lang.get("show_value"))
	}
	private defaultValueOption(): Vnode<any, any> {
		return <div>
			<label class="line noDesc">
				<small>{Lang.get("prefilledValue")}</small>
				<input type="text" class="big" disabled={this.input.required.get()} { ... BindObservable(this.input.defaultValue) }/>
				{ObservableLangChooser(this.study)}
			</label>
		</div>
	}
	private leftRightLabelOption(): Vnode<any, any> {
		return <div class="stretched center">
			<label class="horizontal spacingRight">
				<small>{Lang.get("label_leftChoice")}</small>
				<input class="big" type="url" { ... BindObservable(this.input.leftSideLabel) }/>
				{ObservableLangChooser(this.study)}
			</label>
			<label class="horizontal">
				<small>{Lang.get("label_rightChoice")}</small>
				<input class="big" type="url" { ... BindObservable(this.input.rightSideLabel) }/>
				{ObservableLangChooser(this.study)}
			</label>
		</div>
	}
	private itemsOption(): Vnode<any, any> {
		const removeChoice = (index: number) => {
			this.input.listChoices.remove(index)
		}
		const addItem = () => {
			let name = prompt()
			if(!name)
				return
			this.input.listChoices.push(name)
		}
		
		return <div>
			<h2>{Lang.getWithColon("choices")}</h2>{
			DragContainer((dragTools) => {
				return <div class="listParent">
					<div class="listChild">
						{
							this.input.listChoices.get().map((choiceObs, index) => {
								return dragTools.getDragTarget(index, this.input.listChoices,
									<div class="vertical">
										{dragTools.getDragStarter(index, this.input.listChoices)}
										<input type="text" {...BindObservable(choiceObs)}/>
										{BtnTrash(removeChoice.bind(this, index))}
									</div>
								)
							})
						}
						<div class="spacingTop center">
							{BtnAdd(addItem.bind(this), Lang.get("add"))}
						</div>
					</div>
				</div>
			})
		}</div>
	}
	
	
	private getEntry(): InputEntry {
		return this.inputTypes[this.input.responseType.get()]
	}
	
	public getView(): Vnode<any, any> | null {
		const views = this.getEntry().view()
		
		return DashRow(
			DashElement("stretched",
				{
					content:
						<div>
							<div class="fakeLabel line">
								<small>{Lang.get("text_shown_to_participant")}</small>
								{RichText(this.input.text)}
								{ObservableLangChooser(this.study)}
							</div>
						</div>
				}),
			...views.map((view) => {
				return DashElement("stretched", {content: view})
			})
		)
	}
	public createTypesView(
		categoryView: (title: string, inputViews: Vnode<any, any>[]) => Vnode<any, any>,
		inputView: (title: InputEntry, isActive: boolean, onclick: () => void) => Vnode<any, any> | null
	): Vnode<any, any>[] {
		const categoryViews: Record<string, Vnode<any, any>[]> = {}
		
		const selectedType = this.input.responseType.get()
		for(const key of this.sortedInputTypeKeys) {
			const entry = this.inputTypes[key];
			if(!categoryViews.hasOwnProperty(entry.category))
				categoryViews[entry.category] = []
			
			const view = inputView(entry, key == selectedType,  () => { this.input.responseType.set(key) })
			if(view)
				categoryViews[entry.category].push(view)
		}
		
		
		const output = []
		for(let key in InputCategories) {
			if(categoryViews[key].length)
				output.push(categoryView(InputCategories[key as keyof typeof InputCategories], categoryViews[key]))
		}
		return output
	}
}