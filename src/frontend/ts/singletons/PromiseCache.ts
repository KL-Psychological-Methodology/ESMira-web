const promiseCache: Record<string, Promise<any>> = {};
export const PromiseCache = {
	save<T>(key: string, promise: Promise<T>): Promise<T> {
		const newPromise = promise
			.then((response) => {
				return response //cache return value
			})
			.catch((e) => {
				this.remove(key)
				throw e
			})
		
		promiseCache[key] = newPromise
		return newPromise
	},
	get<T>(key: string, createPromise: () => Promise<T>): Promise<T> {
		if(promiseCache.hasOwnProperty(key))
			return promiseCache[key]
		else
			return this.save(key, createPromise())
	},
	exists(key: string): boolean {
		return promiseCache.hasOwnProperty(key)
	},
	remove(key: string): void {
		if(promiseCache.hasOwnProperty(key))
			delete promiseCache[key]
	}
}