import m from "mithril";
import {PromiseCache} from "../singletons/PromiseCache";
import {FILE_ADMIN} from "../constants/urls";
import {Requests} from "../singletons/Requests";
import {Lang} from "../singletons/Lang";
import {ObservableStructure, ObservableStructureDataType} from "../observable/ObservableStructure";
import {ObservableArray} from "../observable/ObservableArray";


export class Bookmark extends ObservableStructure {
	public url = this.primitive<string>("url", "")
	public alias = this.primitive<string>("alias", "")
}

export class BookmarkLoader {
	private bookmarks?: ObservableArray<ObservableStructureDataType, Bookmark>
	
	public async init(): Promise<BookmarkLoader> {
		return PromiseCache.get("bookmarsList", async() => {
			const bookmarksJson = await Requests.loadJson(`${FILE_ADMIN}?type=GetBookmarks`)
			this.bookmarks = new ObservableArray<ObservableStructureDataType, Bookmark>(
				bookmarksJson,
				null,
				"bookmarksList",
				(data, parent, key) => {
					return new Bookmark(data, parent, key)
				}
			)
			this.bookmarks.addObserver((_origin, _) => {
				m.redraw()
			})
			return this
		})
	}
	
	private getBookmarkIndex(url: string): number {
		return this.getBookmarkList().map((bookmark) => {
			return bookmark.url.get()
		}).indexOf(url)
	}
	
	public isBookmarkListEmpty(): boolean {
		return this.bookmarks!.get().length == 0
	}
	
	public hasBookmark(url: string): boolean {
		return this.getBookmarkIndex(url) >= 0
	}
	
	public getBookmarkList(): Bookmark[] {
		return this.bookmarks?.get() ?? []
	}
	
	public async deleteBookmark(url: string): Promise<void> {
		let response = url
		response = await Requests.loadJson(`${FILE_ADMIN}?type=DeleteBookmark`, "post", `url=${url}`)
		if(response != url)
			throw new Error(Lang.get("error_unknown"))
		this.bookmarks!.remove(this.getBookmarkIndex(url))
	}
	
	public async setBookmark(url: string, alias: string): Promise<void> {
		const bookmarks = this.bookmarks!
		let bookmarkJson = await Requests.loadJson(`${FILE_ADMIN}?type=SetBookmark`, "post", `url=${url}&alias=${alias}`)
		const index = this.getBookmarkIndex(url)
		if(index >= 0)
			bookmarks.get()[index].alias.set(alias)
		else
			bookmarks.push(bookmarkJson)
	}
}