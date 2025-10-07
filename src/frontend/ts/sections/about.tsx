import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import repositorySvg from "../../imgs/dashIcons/github.svg?raw"
import googlePng from "../../imgs/google-play-badge-en.png"
import applePng from "../../imgs/apple-store-badge-en.png"
import {TitleRow} from "../widgets/TitleRow";
import {Section} from "../site/Section";
import {AboutESMiraInterface, AboutESMiraLoader} from "../loader/AboutESMiraLoader";

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
	}
	
	public title(): string {
		return Lang.get("about_esmira")
	}
	
	public getView(): Vnode<any, any> {
		const structure = this.about.structure
		const translations = this.about.translations
		return <div>
			<div class="center horizontalPadding">
				<p class="justify lineSize">{translations["about_text"]}</p>
				<a href="https://play.google.com/store/apps/details?id=at.jodlidev.esmira" target="_blank"><img alt="Android" src={googlePng}/></a>
				&nbsp;
				<a href="https://apps.apple.com/gb/app/esmira/id1538774594" target="_blank"><img alt="iOS" src={applePng}/></a>
			</div>
			
			<br/>
			
			{DashRow(
				DashElement(null, {template: {title: Lang.get("screenshots")}, href: this.getUrl("screenshots")}),
				DashElement(null, {template: {title: Lang.get("publications_using_esmira")}, href: this.getUrl("publications")}),
				DashElement("stretched", {
					href: structure.repository_link,
					template: {
						title: translations["use_for_own_studies"],
						msg: translations["esmira_own_server_get_started"],
						icon: m.trust(repositorySvg)
					},
				})
			)}
			<br/>
			
			{structure.page_about.map((entry) =>
				<div>
					{TitleRow(translations[entry.id])}
					{entry.dash && DashRow(
						...entry.dash.map((dash) =>
							DashElement(null, {
								template: {
									title: translations[`${dash.id}_title`],
									msg: m.trust(translations[`${dash.id}_desc`]),
									innerLinkTitle: dash.screenshots && Lang.get("screenshots"),
									innerLinkHref: dash.screenshots && this.getUrl(dash.screenshots),
									icon: m.trust(dash.icon)
								}
							})
						)
					)}
					
					{entry.urls?.map((url) =>
						<div class="line center">
							<a class="verticalPadding showArrow" target="_blank" href={url.href}>{translations[url.id]}</a>
						</div>
					)}
					
				</div>
			)}
		</div>
	}
}