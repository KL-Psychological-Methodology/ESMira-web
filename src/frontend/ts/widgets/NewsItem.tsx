import m, {Vnode} from "mithril"
import { RssItem } from "../singletons/RssFetcher"
import { Lang } from "../singletons/Lang"

const DAYS_FRESH = 7

export function NewsItem(
    item: RssItem
): Vnode<any, any> {
    const date = new Date(item.date)
    const now = new Date()
    const diffTime = Math.abs(date.getTime() - now.getTime())
    const diffDays = Math.floor(diffTime / (1000 * 3600 * 24))
    let dateString: String
    if(diffDays <= 0) {
        dateString = Lang.get("today")
    } else {
        dateString = Lang.get("x_days_ago", diffDays)
    }
    let classString = "newsItem"
    if(diffDays < DAYS_FRESH){
        classString += " " + "highlight"
    }
    return <div class={`${classString}`}>
            <div class="newsTitle"><a href={`${item.link}`}>{item.title}</a></div>
            <div class="newsDate"><span>{dateString}</span></div>
    </div>
}