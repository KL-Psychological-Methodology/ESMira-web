import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../components/DashRow";
import {DashElement} from "../components/DashElement";
import {Lang} from "../singletons/Lang";
import {TitleRow} from "../components/TitleRow";
import {AboutESMiraInterface, AboutESMiraLoader} from "../loader/AboutESMiraLoader";
import {URL_ABOUT_ESMIRA_SOURCE} from "../constants/urls";
import {DropdownMenu} from "../components/DropdownMenu";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	private about: AboutESMiraInterface
	public static preLoad(): Promise<any>[] {
		return [
			AboutESMiraLoader.load()
		]
	}
	
	constructor(sectionData: SectionData, about: AboutESMiraInterface) {
		super(sectionData)
		this.about = about
		
		window.setTimeout(() => {
			const img = document.getElementById(`screenshots_${this.sectionData.sectionValue}`)
			if(img)
				img.scrollIntoView({behavior: 'smooth'})
		}, 500)
	}
	
	public title(): string {
		return Lang.get("screenshots")
	}
	
	public getView(): Vnode<any, any> {
		const structure = this.about.structure
		const translations = this.about.translations
		return <div>
			{structure.page_screenshots.map((entry) =>
				<div>
					{TitleRow(translations[`${entry.id}_title`])}
					{DashRow(
						...entry.images.map((imageSection) =>
							DashElement(null, {
								content:<div>
									{imageSection.map((screenshot) =>
										DropdownMenu("screenshot",
											<div class="imageBox" id={`screenshots_${entry.id}`}>
												<img alt="screenshot" src={`${URL_ABOUT_ESMIRA_SOURCE}${screenshot.src}`}/>
												{screenshot.desc && <span class="desc smallText">{screenshot.desc}</span>}
											</div>,
											(close) =>
												<div class="screenshotWindow" onclick={close}>
													<img alt="screenshot" src={`${URL_ABOUT_ESMIRA_SOURCE}${screenshot.src}`}/>
													{screenshot.desc && <span class="desc smallText">{screenshot.desc}</span>}
												</div>,
											{fullScreen: true}
										)
									)}
								</div>
								
								
							}))
					)}
				</div>
			)}
			
		</div>
	}
}