import m, {Component, Vnode, VnodeDOM} from "mithril";
import {LoadedStatistics} from "../loader/csv/CsvLoaderCollectionFromCharts";
import {ChartJsBox} from "../helpers/ChartJsBox";
import {ObserverId} from "../observable/BaseObservable";
import {ChartData} from "../data/study/ChartData";
import {LoadingSpinner} from "./LoadingSpinner";
import {ObservablePromise} from "../observable/ObservablePromise";

interface ChartComponentOptions {
	promise: ObservablePromise<LoadedStatistics>
	chart: ChartData
	className?: string //in case the parent has added a className that we need to include
	noSort: boolean
}

class ChartComponent implements Component<ChartComponentOptions, any> {
	private enabled: boolean = false
	private chart?: ChartData
	private chartView?: HTMLElement
	private chartViewBox?: ChartJsBox
	private promiseObserverId?: ObserverId
	
	
	private async drawGraph(chart: ChartData, promise: Promise<LoadedStatistics>, noSort: boolean): Promise<void> {
		this.enabled = false
		m.redraw()
		
		const data = await promise
		const view = this.chartView
		
		if(!view)
			return
		while(view?.hasChildNodes()) {
			view.removeChild(view.lastChild!)
		}
		
		this.chartViewBox = new ChartJsBox(view, data.mainStatistics, data.additionalStatistics ?? {}, chart, noSort)
		this.enabled = true
		m.redraw()
	}
	
	public oncreate(vNode: VnodeDOM<ChartComponentOptions, any>): void {
		const promise = vNode.attrs.promise
		const chart = vNode.attrs.chart
		const noSort = vNode.attrs.noSort
		this.chart = chart
		this.chartView = vNode.dom.getElementsByClassName("chartViewWindow")[0] as HTMLElement
		
		this.drawGraph(chart, promise.get(), noSort)
		this.promiseObserverId = promise.addObserver(() => {
			this.drawGraph(chart, promise.get(), noSort)
		})
	}
	
	public onupdate(vNode: VnodeDOM<ChartComponentOptions, any>): void {
		//when a section is replaced with another section with the same content (but from a different study), mithril js will not reload this ChartView but call onupdate instead
		const promise = vNode.attrs.promise
		const chart = vNode.attrs.chart
		const noSort = vNode.attrs.noSort
		
		if(chart != this.chart) {
			this.chart = chart
			this.drawGraph(chart, promise.get(), noSort)
			this.promiseObserverId?.removeObserver()
			
			this.promiseObserverId = promise.addObserver(() => {
				this.drawGraph(chart, promise.get(), noSort)
			})
		}
	}
	
	public onremove(): void {
		this.promiseObserverId?.removeObserver()
	}
	
	public view(vNode: VnodeDOM<ChartComponentOptions, any>): Vnode<any, any> {
		return <div class={`chartView center ${vNode.attrs.className ?? ""}`}>
			<div class={`chartViewWindow ${this.enabled ? "fadeIn" : ""}`}></div>
			{!this.enabled &&
				LoadingSpinner()
			}
		</div>
	}
}

export function ChartView(chart: ChartData, promise: ObservablePromise<LoadedStatistics>, noSort: boolean = false) {
	return m(ChartComponent, {
		chart: chart,
		promise: promise,
		noSort: noSort
	})
}