import { URL_BLOG_RSS } from "../constants/urls"
import { Requests } from "./Requests"


export type RssItem = {
    title: string
    date: string
    link: string
}

export const RssFetcher = {
    loadFeed(max_num_items: number): Promise<RssItem[]> {
        return Requests.loadRaw(URL_BLOG_RSS).then(
            (feed: string) => {
                const parser = new DOMParser()
                const xmlDoc = parser.parseFromString(feed, "application/xml")
                
                const entries = xmlDoc.getElementsByTagName("entry")
                let items: RssItem[] = []
                for(let i = 0; i < Math.min(entries.length, max_num_items); i++) {
                    const entry = entries[i]
                    const title = entry.getElementsByTagName('title')[0]?.textContent ?? ""
                    const link = entry.getElementsByTagName('link')[0]?.getAttribute('href') ?? ""
                    const date = entry.getElementsByTagName('updated')[0]?.textContent ?? ""
                    
                    items.push({title: title, link: link, date: date})
                }
                
                return items
            }
        )
    }
}