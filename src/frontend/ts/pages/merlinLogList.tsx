import { FILE_ADMIN } from "../constants/urls";
import m, { Vnode } from "mithril";
import { MerlinLogInfo } from "../data/merlinLogs/MerlinLogInfo";
import { Lang } from "../singletons/Lang";
import { Requests } from "../singletons/Requests";
import { Section } from "../site/Section";
import { SectionContent } from "../site/SectionContent";
import commentSvg from "../../imgs/icons/comment.svg?raw"
import { BtnOk, BtnTrash } from "../widgets/BtnWidgets";


export class Content extends SectionContent {
    private readLogs: MerlinLogInfo[] = []
    private unreadLogs: MerlinLogInfo[] = []

    public static preLoad(section: Section): Promise<any>[] {
        return [
            Requests.loadJson(`${FILE_ADMIN}?type=ListMerlinLogs&study_id=${section.getStaticInt("id") ?? 0}`)
        ]
    }
    constructor(section: Section, merlinLogs: MerlinLogInfo[]) {
        super(section)

        this.sortMerlinLogs(merlinLogs)
    }

    public title(): string {
        return Lang.get("merlin_logs")
    }

    private async reloadMerlinLogs(): Promise<void> {
        const logs = await this.section.loader.loadJson(`${FILE_ADMIN}?type=ListMerlinLogs&study_id=${this.section.getStaticInt("id") ?? 0}`)
        this.sortMerlinLogs(logs)
        this.getTools().merlinLogsLoader.setStudyNewLogsRemaining(this.section.getStaticInt("id") ?? 0, this.unreadLogs.length > 0)
    }

    private sortMerlinLogs(merlinLogs: MerlinLogInfo[]) {
        this.readLogs = []
        this.unreadLogs = []

        merlinLogs = merlinLogs.sort(function(a, b) {
            return b.timestamp - a.timestamp
        })

        for(const merlinLog of merlinLogs) {
            merlinLog.printName = this.getName(merlinLog)

            if(merlinLog.seen)
                this.readLogs.push(merlinLog)
            else
                this.unreadLogs.push(merlinLog)
        }
    }

    private getName(merlinLog: MerlinLogInfo): string {
        const date = new Date(merlinLog.timestamp).toLocaleString()
        return merlinLog.note ? `${merlinLog.note} (${date})` : date
    }

    private async deleteMerlinLog(merlinLog: MerlinLogInfo): Promise<void> {
        if(!confirm(Lang.get("confirm_delete_merlin_log", this.getName(merlinLog))))
            return

        await this.section.loader.loadJson(
            `${FILE_ADMIN}?type=DeleteMerlinLog&study_id=${this.section.getStaticInt("id") ?? 0}`,
            "post",
            `timestamp=${merlinLog.timestamp}`
        )
        await this.reloadMerlinLogs()
    }

    private async markMerlinLogAsSeen(merlinLog: MerlinLogInfo): Promise<void> {
        await this.section.loader.loadJson(
            `${FILE_ADMIN}?type=ChangeMerlinLog&study_id=${this.section.getStaticInt("id") ?? 0}`,
            "post",
            `timestamp=${merlinLog.timestamp}&note=${merlinLog.note}&seen=1`
        )
        await this.reloadMerlinLogs()
    }

    private async addNoteToMerlinLog(merlinLog: MerlinLogInfo): Promise<void> {
        const newNote = prompt(Lang.get("prompt_comment"), merlinLog.note)
        if(!newNote)
            return
        
        await this.section.loader.loadJson(
            `${FILE_ADMIN}?type=ChangeMerlinLog&study_id=${this.section.getStaticInt("id") ?? 0}`,
            "post",
            `timestamp=${merlinLog.timestamp}&note=${newNote}&seen=${merlinLog.seen ? 1 : 0}`
        )
        await this.reloadMerlinLogs()
    }

    private getMerlinLogList(merlinLogs: MerlinLogInfo[]): Vnode<any, any> {
        return <div class="listParent">
            <div class="listChild error_list">
                {merlinLogs.map((merlinLog) => 
                    <div>
                        {BtnTrash(this.deleteMerlinLog.bind(this, merlinLog))}
                        {!merlinLog.seen &&
                            BtnOk(this.markMerlinLogAsSeen.bind(this, merlinLog))
                        }
                        <a onclick={this.addNoteToMerlinLog.bind(this, merlinLog)}>
                            {m.trust(commentSvg)}
                        </a>
                        <a href={this.getUrl(`merlinLogView,timestamp:${merlinLog.timestamp},note:${btoa(merlinLog.note)}`)}>{merlinLog.printName}</a>
                        {merlinLog.seen &&
                            <span class="extraNote">{Lang.get("seen")}</span>
                        }
                    </div>
                )}
            </div>
        </div>
    }

    public getView(): Vnode<any, any> {
        return <div>
            {this.readLogs.length == 0 && this.unreadLogs.length == 0 &&
                <div><span>{Lang.get("no_merlin_logs")}</span></div>
            }
            {this.getMerlinLogList(this.unreadLogs)}
            {this.readLogs.length != 0 && this.unreadLogs.length != 0 &&
                <hr/>
            }
            {this.getMerlinLogList(this.readLogs)}
        </div>
    }
}