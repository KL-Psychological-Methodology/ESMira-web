import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {AboutESMiraLoader, ESMiraPublicationsInterface} from "../loader/AboutESMiraLoader";

export class Content extends SectionContent {
	private publications: ESMiraPublicationsInterface
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			AboutESMiraLoader.loadPublications()
		]
	}
	
	constructor(section: Section, publications: ESMiraPublicationsInterface) {
		super(section)
		this.publications = publications
	}
	
	public title(): string {
		return Lang.get("publications_using_esmira")
	}
	
	
	
	public getView(): Vnode<any, any> {
		return <div>
			<h2>{Lang.getWithColon("main_publication")}</h2>
			{DashRow(
				DashElement("stretched", {
					content:
						<div>
							<div class="spacingLeft spacingRight">
								<p class="hanging">
									Lewetz, D., Stieger, S. (2023). ESMira: A decentralized open-source application for collecting experience sampling
									data. <i>Behavior Research Methods</i>.
									<br/>
									<a class="showArrow" href="https://doi.org/10.3758/s13428-023-02194-2" target="_blank">https://doi.org/10.3758/s13428-023-02194-2</a>
								</p>
							</div>
						</div>
				})
			)}
			
			{
				this.publications.years.map((year) => {
					const publications = this.publications.entries[year]
					return <div>
						<h2>{Lang.get("colon", year.toString())}</h2>
						{DashRow(...publications.map((pub) =>
							DashElement("stretched", {
								content:
									<div class="spacingLeft spacingRight">
										<p class="hanging justify">
											{!!pub.title && m.trust(pub.title)}
											<br/>
											{!!pub.url && <a class="showArrow" href={pub.url} target="_blank">{pub.url}</a>}
										</p>
									</div>
							})
							)
						)}
					</div>
				})
			}
		</div>
	}
}