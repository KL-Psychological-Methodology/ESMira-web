import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BtnAdd, BtnTransfer, BtnTrash} from "../components/Buttons";
import {SectionData} from "../site/SectionData";
import {FILE_ADMIN} from "../constants/urls";
import {getReadableByteSize, safeConfirm} from "../constants/methods";
import {Requests} from "../singletons/Requests";

interface SnapshotEntry {
	name: string
	created: number
	size: number
}

export class Content extends SectionContent {
	private snapshots: SnapshotEntry[] = []
	
	constructor(sectionData: SectionData, snapshots: SnapshotEntry[]) {
		super(sectionData)
		this.snapshots = snapshots
	}
	
	async preInit(): Promise<any> {
		await this.loadSnapshots()
	}
	
	private async loadSnapshots(): Promise<void> {
		const snapshots = await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=ListSnapshots`) as SnapshotEntry[]
		snapshots.sort((a, b) => b.created - a.created)
		this.snapshots = snapshots
		m.redraw()
	}
	
	public title(): string {
		return Lang.get("snapshots")
	}
	
	private async createSnapshot(): Promise<void> {
		const name = prompt(Lang.get("title"))
		
		if(!name) {
			return
		}
		
		try {
			await this.sectionData.loader.loadWithSSE(
				`${FILE_ADMIN}?type=CreateSnapshot&name=${name}`,
				percent => Lang.get("creating_snapshot", percent)
			)
			await this.loadSnapshots()
			this.sectionData.loader.info(Lang.get("created_snapshot"))
		}
		catch(e) {
			this.sectionData.loader.error(Lang.get("creating_snapshot_failed", (e as Error).message || (e as any).toString()))
		}
	}
	
	private async restoreSnapshot(snapshot: SnapshotEntry): Promise<void> {
		if(!safeConfirm(Lang.get("restore_snapshot_confirm"))) {
			return
		}
		const loader = this.sectionData.loader
		await loader.showLoader(new Promise(async (resolve, reject) => {
			try {
				await Requests.loadJson(`${FILE_ADMIN}?type=RestoreSnapshotPrepare`, "post", `name=${snapshot.name}`)
				await Requests.loadWithSSE(
					`${FILE_ADMIN}?type=UpdateStepReplace`,
						percent => loader.update(Lang.get("state_updating", percent))
				)
				await Requests.loadWithSSE(
					`${FILE_ADMIN}?type=RestoreSnapshotData`,
					percent => loader.update(Lang.get("state_restoring_data", percent))
				)
				
				resolve(null)
			}
			catch (e) {
				reject(e)
			}
		}), Lang.get("state_preparing"))
		
		alert(Lang.get("info_snapshot_restore_complete", snapshot.name))
		window.location.reload()
	}
	
	private async removeSnapshot(snapshot: SnapshotEntry): Promise<void> {
		if(!confirm()) {
			return
		}
		await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=DeleteSnapshot`, "post", `snapshotName=${snapshot.name}`)
		await this.loadSnapshots()
	}
	
	public getView(): Vnode<any, any> {
		return <div>
			<div class="smallText spacingLeft spacingRight">
				{Lang.get("snapshot_description")}
			</div>
			<div class="center">
				<div class="spacingTop spacingBottom">
					{BtnAdd(this.createSnapshot.bind(this), Lang.get("create_snapshot"))}
				</div>
				
				{!!this.snapshots.length &&
					<table style="width: 100%">
						<thead>
						<tr>
							<th>{Lang.get("title")}</th>
							<th>{Lang.get("date")}</th>
							<th>{Lang.get("size")}</th>
							<td></td>
						</tr>
						</thead>
						<tbody>
						{this.snapshots.map(entry =>
							<tr>
								<td>{entry.name}</td>
								<td title={new Date(entry.created * 1000).toLocaleTimeString()}>{new Date(entry.created * 1000).toLocaleDateString()}</td>
								<td>{getReadableByteSize(entry.size)}</td>
								<td>
									{BtnTransfer(this.restoreSnapshot.bind(this, entry), "", Lang.get("restore_snapshot"))}
									{/*{BtnCustom(m.trust(transferSvg), this.restoreSnapshot.bind(this, entry), "", Lang.get("restore_snapshot"))}*/}
									{BtnTrash(this.removeSnapshot.bind(this, entry))}
								</td>
							</tr>
						)}
						</tbody>
					</table>
				}
			</div>
		</div>
	}
}