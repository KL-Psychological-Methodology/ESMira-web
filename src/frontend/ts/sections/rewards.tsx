import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { DashRow } from "../components/DashRow";
import { DashElement } from "../components/DashElement";
import { Lang } from "../singletons/Lang";
import { BindObservable, ConstrainedNumberTransformer, OnBeforeChangeTransformer } from "../components/BindObservable";
import { ObservableLangChooser } from "../components/ObservableLangChooser";
import { RichText } from "../components/RichText";
import warnSvg from "../../imgs/icons/warn.svg?raw"
import rewardsSvg from "../../imgs/dashIcons/rewards.svg?raw"
import { BtnCustom } from "../components/Buttons";
import { SectionData } from "../site/SectionData";

export class Content extends SectionContent {
	private faultyEmailContent = false

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}

	public title(): string {
		return Lang.get("reward_system")
	}
	public titleExtra(): Vnode<any, any> | null {
		let study = this.getStudyOrThrow()
		return study.enableRewardSystem.get() && this.getAdmin().getTools().hasPermission("reward", study.id.get())
			? <a href={this.getUrl("rewardCodes")}>
				{BtnCustom(m.trust(rewardsSvg), undefined, Lang.get("validate_reward_code"))}
			</a>
			: null
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			{DashRow(
				DashElement("stretched", {
					content:
						<div>
							<span>{Lang.get("desc_reward_system")}</span>

							<br />
							<br />
							<div class="center">
								<label class="noTitle noDesc">
									<input type="checkbox"{...BindObservable(study.enableRewardSystem)} />
									<span>{Lang.get("enable_reward_system")}</span>
								</label>
							</div>
						</div>
				})
			)}
			{study.enableRewardSystem.get() &&
				DashRow(
					DashElement(null,
						{
							content:
								<div class="center">
									<h2>{Lang.getWithColon("visible_after")}</h2>
									<label class="noDesc noTitle">
										<input type="number" min="0" {...BindObservable(study.rewardVisibleAfterDays, new ConstrainedNumberTransformer(0, undefined))} />
										<span>{Lang.get("days")}</span>
									</label>
								</div>
						}
					),
					DashElement(null, {
						content:
							<div class="center">
								<h2 class="center">{Lang.getWithColon("minimal_number_of_entries_required")}</h2>

								<table style="width: 100%;">
									{study.questionnaires.get().map((questionnaire) =>
										<tr>
											<td>
												<span>{questionnaire.getTitle()}</span>
											</td>
											<td>
												<input type="number" min="0" {...BindObservable(questionnaire.minDataSetsForReward, new ConstrainedNumberTransformer(0, undefined))} />
											</td>
										</tr>)}

								</table>
							</div>
					}),

					DashElement("stretched", {
						content:
							<div>
								<h2 class="center">{Lang.get("email_content")}</h2>
								<label class="spacingTop line">
									<textarea {...BindObservable(
										study.rewardEmailContent,
										new OnBeforeChangeTransformer<string>(study.rewardEmailContent, (_, after) => {
											const newValue = after
											this.faultyEmailContent = newValue.length > 0 && newValue.indexOf("[[CODE]]") === -1
											return after
										})
									)}></textarea>
									<small class={this.faultyEmailContent ? "highlight" : ""}>{Lang.get("info_reward_email_content")}</small>
									{this.faultyEmailContent &&
										<div class="warn">{m.trust(warnSvg)}</div>
									}
									{ObservableLangChooser(study)}
								</label>
							</div>
					}),
					DashElement("stretched", {
						content:
							<div>
								<h2 class="center">{Lang.get("reward_code_instructions")}</h2>
								<div class="fakeLabel spacingTop line">
									{RichText(study.rewardInstructions)}
									{ObservableLangChooser(study)}
								</div>
							</div>
					})
				)

			}
		</div>
	}
}