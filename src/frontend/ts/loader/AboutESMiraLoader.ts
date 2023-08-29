import {URL_ABOUT_ESMIRA_JSON, URL_ABOUT_ESMIRA_PUBLICATIONS_JSON, URL_ABOUT_ESMIRA_STRUCTURE_JSON} from "../constants/urls";
import {Lang} from "../singletons/Lang";
import {PromiseCache} from "../singletons/PromiseCache";
import {Requests} from "../singletons/Requests";

export interface AboutESMiraInterface {
	structure: {
		repository_link: string,
		page_about: {
			id: string
			dash?: {
				id: string,
				screenshots?: string,
				icon: string
			}[],
			urls?: {
				id: string,
				href: string
			}[]
		}[],
		page_screenshots: {
			id: string
			images: {
				src: string,
				desc?: string
			}[][]
		}[],
		page_instances: {
			title: string,
			description: string,
			logo: string,
			url: string
		}[]
	}
	translations: Record<string, string>
}

export interface ESMiraPublicationsInterface {
	years: number[],
	entries: Record<number, Publication[]>
}

interface Publication {
	year: number,
	title: string
	url: string
}

export class AboutESMiraLoader {
	public static load(): Promise<AboutESMiraInterface> {
		return PromiseCache.get("aboutESMira", async () => {
			const structure = await Requests.loadRaw(URL_ABOUT_ESMIRA_STRUCTURE_JSON)
			try {
				const lang = await Requests.loadRaw(URL_ABOUT_ESMIRA_JSON.replace("%s", Lang.code))
				return {
					structure: JSON.parse(structure),
					translations: JSON.parse(lang)
				}
			}
			catch(e) {
				const fallbackLang = await Requests.loadRaw(URL_ABOUT_ESMIRA_JSON.replace("%s", "en"))
				return {
					structure: JSON.parse(structure),
					translations: JSON.parse(fallbackLang)
				}
			}
		})
	}
	
	public static loadPublications(): Promise<ESMiraPublicationsInterface> {
		return PromiseCache.get("ESMiraPublications", async () => {
			const publicationsArray: Publication[] = JSON.parse(await Requests.loadRaw(URL_ABOUT_ESMIRA_PUBLICATIONS_JSON))
			
			const publications: Record<number,Publication[]> = {}
			const years: number[] = []
			for(let publication of publicationsArray) {
				if(!publications.hasOwnProperty(publication.year)) {
					publications[publication.year] = []
					years.push(publication.year)
				}
				publications[publication.year].push(publication)
			}
			for(const year in publications) {
				publications[year].sort((a, b) => {
					if(a.title > b.title)
						return 1
					else if(a.title < b.title)
						return -1
					else
						return 0
				})
			}
			years.sort((a, b) => {
				if(a < b)
					return 1
				else if(a > b)
					return -1
				else
					return 0
			})
			
			return {
				years: years,
				entries: publications
			}
		})
	}
}