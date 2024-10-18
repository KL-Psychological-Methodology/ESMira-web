/**
 * This container holds all observers created saved under their address (which is derived from their key and their parents address).
 * Each structure of observable share the same SharedForObservable and every observable in that structure has a reference to it.
 * Having a shared object makes sure, that observers are still valid even if the whole data structure was replaced.
 *
 * See usage in {@link BaseObservable}
 */
export class SharedForObservable {
	public observerContainer: Record<string, Record<number, (... args: any[]) => void>> = {}
	public idCounter = 0
}