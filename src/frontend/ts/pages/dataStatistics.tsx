import { SectionAlternative, SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { DashRow } from "../widgets/DashRow";
import { DashElement } from "../widgets/DashElement";
import { Lang } from "../singletons/Lang";
import dataTableSvg from "../../imgs/icons/table.svg?raw"
import calculateSvg from "../../imgs/dashIcons/calculate.svg?raw"
import summarySvg from "../../imgs/dashIcons/summary.svg?raw"
import participantsSvg from "../../imgs/icons/participants.svg?raw"
import webAccessSvg from "../../imgs/devices/web.svg?raw"
import publicStatisticsSvg from "../../imgs/dashIcons/publicStatistics.svg?raw"
import rewardsSvg from "../../imgs/dashIcons/rewards.svg?raw"
import merlinLogsSvg from "../../imgs/dashIcons/merlinLogs.svg?raw"
import { Section } from "../site/Section";
import { SharedUrlAlternatives } from "../helpers/SharedUrlAlternatives";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	public title(): string {
		return Lang.get("data")
	}

	public hasAlternatives(): boolean {
		return true
	}
	public getAlternatives(): SectionAlternative[] | null {
		return SharedUrlAlternatives.studyAlternatives(this, "data")
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const usesMerlinScripts = study.hasMerlinScripts()
		const hasPublicCharts = study.publicStatistics.charts.get().length > 0
		const usesRewardSystem = study.enableRewardSystem.get()
		const hasNewMerlinLogs = this.getTools().merlinLogsLoader.studiesWithNewMerlinLogsList[this.section.getStaticInt("id") || 0] || false
		const useSimplified = !this.hasPermission('read', this.section.getStaticInt("id") || 0)
		if (useSimplified) {
			return <div>
				<span class="stretched smallText">{Lang.get("info_charts_loadingTime")}</span>
				{DashRow(
					DashElement(null, {
						template: {
							title: Lang.get("participants"),
							icon: m.trust(participantsSvg)
						},
						href: this.getUrl("statsParticipants")
					}),
					DashElement(null, {
						template: {
							title: Lang.get("public_statistics"),
							icon: m.trust(publicStatisticsSvg)
						},
						href: this.getUrl("publicStatistics")
					})
				)}
			</div>
		}
		return <div>
			<span class="stretched smallText">{Lang.get("info_charts_loadingTime")}</span>
			{DashRow(
				DashElement(null, {
					template: {
						title: Lang.get("data_table"),
						icon: m.trust(dataTableSvg)
					},
					href: this.getUrl("dataList")
				}),
				DashElement(null, {
					template: {
						title: Lang.get("calculate_chart_from_data"),
						icon: m.trust(calculateSvg)
					},
					href: this.getUrl("chartEdit:calc")
				}),
				DashElement(null, {
					template: {
						title: Lang.get("summary"),
						icon: m.trust(summarySvg)
					},
					href: this.getUrl("statsStudy")
				}),
				DashElement(null, {
					template: {
						title: Lang.get("participants"),
						icon: m.trust(participantsSvg)
					},
					href: this.getUrl("statsParticipants")
				}),
				DashElement(null, {
					template: {
						title: Lang.get("web_access"),
						icon: m.trust(webAccessSvg)
					},
					href: this.getUrl("statsWeb")
				}),
				hasPublicCharts && DashElement(null, {
					template: {
						title: Lang.get("public_statistics"),
						icon: m.trust(publicStatisticsSvg)
					},
					href: this.getUrl("publicStatistics")
				}),
				(hasNewMerlinLogs || usesMerlinScripts) && DashElement(null, {
					highlight: hasNewMerlinLogs,
					template: {
						title: Lang.get("merlin_logs"),
						icon: m.trust(merlinLogsSvg)
					},
					href: this.getUrl("merlinLogList")
				}),
				usesRewardSystem && DashElement(null, {
					template: {
						title: Lang.get("validate_reward_code"),
						icon: m.trust(rewardsSvg)
					},
					href: this.getUrl("rewardCodes")
				})
			)
			}</div>
	}
}