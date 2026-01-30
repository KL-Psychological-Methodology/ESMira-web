import {ObservablePrimitive} from "./ObservablePrimitive";
import {BaseObservable} from "./BaseObservable";
import {ObservableTypes} from "./types/ObservableTypes";

export class LangCodeObservable extends ObservablePrimitive<string> {
	isDifferent(): boolean {
		return false
	}
	
	hasMutated(_turnedDifferent: boolean = false, _forceIsDifferent: boolean = false, target: BaseObservable<ObservableTypes> = this) {
		this.runObservers(false, target)
	}
}