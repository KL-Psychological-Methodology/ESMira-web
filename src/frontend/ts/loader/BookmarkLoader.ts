import m from "mithril";
import { ObservableRecord } from "../observable/ObservableRecord";
import { PromiseCache } from "../singletons/PromiseCache";
import { FILE_ADMIN } from "../constants/urls";
import { Requests } from "../singletons/Requests";
import { Lang } from "../singletons/Lang";

export type BookmarksDataType = ObservableRecord<string, string>

export class BookmarkLoader {
    private readonly bookmarks = new ObservableRecord<string, string>({}, "bookmarks")
    
    constructor() {
        this.loadBookmarkList()
        this.bookmarks.addObserver((_origin, _) => {
            m.redraw()
        })
    }

    public hasBookmark(url: string): boolean {
        return this.bookmarks.exists(url)
    }

    public loadBookmarkList(): Promise<BookmarksDataType> {
        return PromiseCache.get("bookmarsList", async () => {
            const bookmarksJson: Record<string, string> = await Requests.loadJson(`${FILE_ADMIN}?type=GetBookmarks`)
            this.bookmarks.set(bookmarksJson)
            return this.bookmarks
        })
    }

    public async deleteBookmark(url: string): Promise<void> {
        let response = url
        response = await Requests.loadJson(`${FILE_ADMIN}?type=DeleteBookmark`, "post", `url=${url}`)
        if(response != url)
            throw new Error(Lang.get("error_unknown"))
        this.bookmarks.remove(url)
    }

    public async setBookmark(url: string, name: string): Promise<void> {
        let response = url
        response = await Requests.loadJson(`${FILE_ADMIN}?type=SetBookmark`, "post", `url=${url}&name=${name}`)
        if(response != url)
            throw new Error(Lang.get("error_unknown"))
        this.bookmarks.add(url, name)
    }
}