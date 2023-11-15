/**
 * Dynamic values are a way of communicating between pages. They are shared between pages and can change at any time.
 * They are saved in an observable and have to be loaded (or checked for changes) on each redraw
 */
export interface DynamicValues {
	showSaveButton: boolean
	showPublishButton: boolean
	accessKey: string
	publicAccessKeyIndex: number
	disabledAccessKeyIndex: number
	studiesIndex: number
	questionnaireIndex: number
	pageIndex: number,
	joinTimestamp: number,
	owner: string,
}