import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import {TitleRow} from "../widgets/TitleRow";
import {Section} from "../site/Section";
import {AboutESMiraInterface, AboutESMiraLoader} from "../loader/AboutESMiraLoader";
import {URL_ABOUT_ESMIRA_SOURCE} from "../constants/urls";
import {DropdownMenu} from "../widgets/DropdownMenu";

export class Content extends SectionContent {
	private about: AboutESMiraInterface
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			AboutESMiraLoader.load()
		]
	}
	
	constructor(section: Section, about: AboutESMiraInterface) {
		super(section)
		this.about = about
		
		window.setTimeout(() => {
			const img = document.getElementById(`screenshots_${this.section.sectionValue}`);
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