import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import { Section } from "../site/Section";
import { FILE_ADMIN } from "../constants/urls";
import {Requests} from "../singletons/Requests";
import { Lang } from "../singletons/Lang";


interface MerlinLogComponentOptions {
    merlinLog: string
}

class MerlinLogComponent implements Component<MerlinLogComponentOptions, any> {
    public oncreate(vNode: VnodeDOM<MerlinLogComponentOptions, any>): void {
        const listView = vNode.dom.getElementsByClassName("merlinLogList")[0] as HTMLElement
        
        const lines = vNode.attrs.merlinLog.split("\n")
        for(const element of lines) {
            const view = document.createElement("div")
            view.classList.add("line")
            view.innerText = element
            listView.appendChild(view)
        }
    }

    public view(): Vnode<any, any> {
        return <div>
            <div class="merlinLogList"></div>
        </div>
    }
}

export class Content extends SectionContent {
    private readonly merlinLog: string
    public static preLoad(section: Section): Promise<any>[] {
        return [
            Requests.loadRaw(`${FILE_ADMIN}?type=GetMerlinLog&study_id=${section.getStaticInt("id") ?? 0}&timestamp=${section.getStaticInt("timestamp")}`)
        ]
    }
    constructor(section: Section, merlinLog: string) {
        super(section)
        this.merlinLog = merlinLog
    }

    public title(): string {
        return Lang.get("merlin_logs")
    }

    public getView(): Vnode<any, any> {
        return m(MerlinLogComponent, {merlinLog: this.merlinLog})
    }
}