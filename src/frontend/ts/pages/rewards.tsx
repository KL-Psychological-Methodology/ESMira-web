import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import {BindObservable, OnBeforeChangeTransformer} from "../widgets/BindObservable";
import {ObservableLangChooser} from "../widgets/ObservableLangChooser";
import {RichText} from "../widgets/RichText";
import warnSvg from "../../imgs/icons/warn.svg?raw"
import {Section} from "../site/Section";

export class Content extends SectionContent {
	private faultyEmailContent = false
	
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	
	public title(): string {
		return Lang.get("reward_system")
	}
	public titleExtra(): Vnode<any, any> | null {
		return this.getStudyOrThrow().enableRewardSystem.get()
			? <a class="right" href={this.getUrl("rewardCodes")}>{Lang.get("validate_reward_code")}</a>
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
							
							<br/>
							<br/>
							<div class="center">
								<label class="noTitle noDesc">
									<input type="checkbox"{... BindObservable(study.enableRewardSystem)}/>
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
										<input type="number" {... BindObservable(study.rewardVisibleAfterDays)}/>
										<span>{Lang.get("days")}</span>
									</label>
								</div>
						}
					),
					DashElement(null, {
						content:
							<div class="listParent">
								<h2 class="center">{Lang.getWithColon("minimal_number_of_entries_required")}</h2>
								
								<table class="listChild">
									{study.questionnaires.get().map((questionnaire) =>
										<tr>
											<td>
												<span>{questionnaire.getTitle()}</span>
											</td>
											<td>
												<input type="number" {... BindObservable(questionnaire.minDataSetsForReward)}/>
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
									<textarea {... BindObservable(
										study.rewardEmailContent,
										new OnBeforeChangeTransformer<string>((_, after) => {
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