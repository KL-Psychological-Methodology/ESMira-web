import m, {Vnode} from "mithril"
import {Lang} from "../singletons/Lang";
import {TranslationRootInterface} from "../observable/interfaces/TranslationRootInterface";


let langNames: Record<string, string> | null = null

export function ObservableLangChooser(obs: TranslationRootInterface): Vnode<any, any> {
	if(obs.langCodes.get().length <= 1)
		return <div></div>
	else if(langNames == null) {
		import(`../../langCodes/${Lang.code}.json`)
			.then((loadedLangNames) => {
				langNames = loadedLangNames
				m.redraw()
			})
		return <div></div>
	}
	
	const changeLang = (langCode: string) => {
		obs.currentLangCode.set(langCode)
		m.redraw()
	}
	
	return <div class="observableLangChooser" title={Lang.get("language")}>
		{
			obs.langCodes.get().map((langCode) =>
				<span
					class={langCode.get() == obs.currentLangCode.get() ? "element clickable selected" : "element clickable"}
					onclick={() => changeLang(langCode.get())}
				>{langNames![langCode.get()] ?? langCode.get()}</span>
			)
		}
	</div>
}