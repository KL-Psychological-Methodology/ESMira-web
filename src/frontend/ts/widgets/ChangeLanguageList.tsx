import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BindObservable, OnBeforeChangeTransformer} from "./BindObservable";
import {TranslationRootInterface} from "../observable/interfaces/TranslationRootInterface";
import {BtnTrash} from "./BtnWidgets";

export class ChangeLanguageList {
	public readonly promise: Promise<void>
	private readonly langCodeNames: { langName: string, langCode: string }[] = []
	private readonly getTranslationRoot: () => TranslationRootInterface
	
	constructor(getTranslationRoot: () => TranslationRootInterface) {
		this.getTranslationRoot = getTranslationRoot
		this.promise = import(`../../langCodes/${Lang.code}.json`)
			.then(({default: data}) => data)
			.then((langCollection: Record<string, string>) => {
				for(let code in langCollection) {
					this.langCodeNames.push({langName: langCollection[code], langCode: code})
				}
			})
	}
	
	private addLang(translationRoot: TranslationRootInterface, e: InputEvent): void {
		const element = e.target as HTMLSelectElement
		const langCode = element.value
		
		translationRoot.langCodes.push(langCode)
		translationRoot.addLanguage(langCode)
		element.selectedIndex = 0
		console.log(translationRoot, langCode)
	}
	
	private removeLang(translationRoot: TranslationRootInterface, langCode: string): void {
		const index = translationRoot.langCodes.indexOf(langCode)
		translationRoot.langCodes.remove(index)
		translationRoot.removeLanguage(langCode)
		
		if(langCode === translationRoot.defaultLang.get())
			translationRoot.defaultLang.set(translationRoot.langCodes.get()[0].get() ?? "unknown")
	}
	
	public getView(): Vnode<any, any> {
		const translationRoot = this.getTranslationRoot()
		const langCodes = translationRoot.langCodes.get()
		return <div class="listParent">
			<div class="listChild">
				{langCodes.map((langCode) =>
					<div>
						{ langCodes.length > 1 &&
							BtnTrash(this.removeLang.bind(this, translationRoot, langCode.get()))
						}
						
						<label class="middle">
							<small>{Lang.get("language")}</small>
							<select {... BindObservable(langCode, new OnBeforeChangeTransformer<string>((before, after) => {
								translationRoot.renameLanguage(before, after)
								if(translationRoot.defaultLang.get() == before)
									translationRoot.defaultLang.set(after)
								return after
							}))}>
								<option>unnamed</option>
								{this.langCodeNames.map((entry) => <option value={entry.langCode}>{entry.langName}</option>)}
							</select>
						</label>
						<label>
							<input type="radio" name="default_language" checked={translationRoot.defaultLang.get() == langCode.get()} onchange={function(this:HTMLInputElement) {
								if(this.checked)
									translationRoot.defaultLang.set(langCode.get())
							}}/>
							<span>{Lang.get("default_language")}</span>
						</label>
					
					</div>
				)}
				<div class="spacingTop">
					<label class="middle spacingLeft">
						<small>{Lang.get("language")}</small>
						
						<select class="smallText" onchange={this.addLang.bind(this, translationRoot)}>
							<option>{Lang.get("select_to_add")}</option>
							{this.langCodeNames.map((entry) => <option value={entry.langCode}>{entry.langName}</option>)}
						</select>
					</label>
				</div>
			</div>
		</div>
		
	}
}