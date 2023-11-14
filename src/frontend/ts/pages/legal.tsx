import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import chartJsIco from "../../imgs/libFavicons/chartJs.ico"
import jsonEditorIco from "../../imgs/libFavicons/jsonEditor.ico"
import fullCalendarPng from "../../imgs/libFavicons/fullCalendar.png"
import mithrilSvg from "../../imgs/libFavicons/mithrilJs.svg"
import papaparseIco from "../../imgs/libFavicons/papaparse.ico"
import tiptapSvg from "../../imgs/libFavicons/tiptap.svg"
import webpackIco from "../../imgs/libFavicons/webpack.ico"
import ktorPng from "../../imgs/libFavicons/ktor.png"
import markwonIco from "../../imgs/libFavicons/markwon.ico"
import {Section} from "../site/Section";
import {PromiseCache} from "../singletons/PromiseCache";
import {FILE_SETTINGS} from "../constants/urls";
import {Requests} from "../singletons/Requests";
import {TabBar} from "../widgets/TabBar";
import {TitleRow} from "../widgets/TitleRow";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";

export class Content extends SectionContent {
	private tabIndex = new ObservablePrimitive(0, null, "legal")
	private readonly showTabs: boolean
	private readonly impressum: string
	private readonly privacyPolicy: string
	
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			PromiseCache.get("legal", () => {
				return Requests.loadJson(FILE_SETTINGS + "?type=legal")
			})
		]
	}
	
	constructor(section: Section, legal: {impressum: string, privacyPolicy: string}) {
		super(section)
		this.impressum = legal.impressum
		this.privacyPolicy = legal.privacyPolicy
		this.showTabs = !!this.impressum || !! this.privacyPolicy
	}
	
	public title(): string {
		return Lang.get("impressum")
	}
	
	public getView(): Vnode<any, any> {
		return this.showTabs
			? TabBar(this.tabIndex, [
					{
						title: Lang.get("used_libraries"),
						view: () => this.getLibrariesView()
					},
					this.impressum.length != 0 && {
						title: Lang.get("impressum"),
						view: () => <div>{m.trust(this.impressum)}</div>
					},
					this.privacyPolicy.length != 0 && {
						title: Lang.get("privacyPolicy"),
						view: () => <div>{m.trust(this.privacyPolicy)}</div>
					}
				]
			)
			: <div>
				{TitleRow(Lang.getWithColon("used_libraries"))}
				{this.getLibrariesView()}
			</div>
	}
	
	private getLibraryEntry(title: string, repository: string, iconSrc?: string): Vnode<any, any> {
		return DashElement(null, {
			content: <div>
				<h2>
					{iconSrc && <img class="middle spacingRight" height="16" src={iconSrc} alt=""/>}
					<span class="middle">{title}</span>
				</h2>
				<a href={repository} target="_blank">{repository}</a>
			</div>
		})
	}
	
	private getLibrariesView(): Vnode<any, any> {
		return<div>
			{DashRow(
				this.getLibraryEntry(
					"Chart.js",
					"https://github.com/chartjs/Chart.js",
					chartJsIco
				),
				this.getLibraryEntry(
					"JSON Editor",
					"https://github.com/josdejong/jsoneditor",
					jsonEditorIco
				),
				this.getLibraryEntry(
					"FullCalendar",
					"https://github.com/fullcalendar/fullcalendar",
					fullCalendarPng
				),
				this.getLibraryEntry(
					"markdown-it",
					"https://github.com/markdown-it/markdown-it",
				),
				this.getLibraryEntry(
					"Mithril.js",
					"https://github.com/MithrilJS/mithril.js",
					mithrilSvg
				),
				this.getLibraryEntry(
					"Papa Parse",
					"https://github.com/mholt/PapaParse",
					papaparseIco
				),
				this.getLibraryEntry(
					"qrcode-generator",
					"https://github.com/kazuhikoarase/qrcode-generator",
				),
				this.getLibraryEntry(
					"tiptap",
					"https://github.com/ueberdosis/tiptap",
					tiptapSvg
				),
				this.getLibraryEntry(
					"webpack",
					"https://github.com/webpack/webpack",
					webpackIco
				)
			)}
			
			
			{TitleRow(Lang.getWithColon("Android"))}
			
			{DashRow(
				this.getLibraryEntry(
					"Accompanist",
					"https://github.com/google/accompanist",
				),
				this.getLibraryEntry(
					"Android-Debug-Database ",
					"https://github.com/amitshekhariitbhu/Android-Debug-Database",
				),
				this.getLibraryEntry(
					"Kotlin Multiplatform",
					"https://github.com/JetBrains/kotlin",
				),
				this.getLibraryEntry(
					"kotlinx.serialization",
					"https://github.com/Kotlin/kotlinx.serialization",
				),
				this.getLibraryEntry(
					"Ktor",
					"https://github.com/ktorio/ktor",
					ktorPng
				),
				this.getLibraryEntry(
					"Markwon",
					"https://github.com/noties/Markwon",
					markwonIco
				),
				this.getLibraryEntry(
					"MPAndroidChart",
					"https://github.com/PhilJay/MPAndroidChart",
				),
				this.getLibraryEntry(
					"zxing-android-embedded",
					"https://github.com/journeyapps/zxing-android-embedded",
				),
			)}
			
			{TitleRow(Lang.getWithColon("iOS"))}
			
			{DashRow(
				this.getLibraryEntry(
					"Charts",
					"https://github.com/danielgindi/Charts",
				),
				this.getLibraryEntry(
					"CodeScanner",
					"https://github.com/twostraws/CodeScanner",
				),
				this.getLibraryEntry(
					"URLImage",
					"https://github.com/dmytro-anokhin/url-image",
				)
			)}
		</div>
	}
}