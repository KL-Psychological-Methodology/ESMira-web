/**
 * Having a shared class makes sure that observers are still valid even if the whole data structure was replaced
 */
export class SharedForObservable {
	public observerContainer: Record<string, Record<number, (... args: any[]) => void>> = {}
	public idCounter = 0
}