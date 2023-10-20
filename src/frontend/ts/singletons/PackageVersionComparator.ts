
const cache: Record<string, PackageVersionComparatorImpl> = {}

const regExp = /(\d+)\.(\d+)\.(\d+)(\D*)(\d*)/

class PackageVersionComparatorImpl {
	private readonly saved: Record<string, boolean> = {}
	private readonly regExpData: RegExpMatchArray | null
	
	constructor(oldPackage: string) {
		this.regExpData = oldPackage.match(regExp)
	}
	
	isBelowThen(newPackage: string): boolean {
		if(this.saved.hasOwnProperty(newPackage))
			return this.saved[newPackage]
		
		const [matchOld, majorOld, minorOld, patchOld, devNameOld, devBuiltOld] = this.regExpData ?? []
		const [matchNew, majorNew, minorNew, patchNew, devNameNew, devBuiltNew] = newPackage.match(regExp) ?? []
		
		
		const r = !!matchOld && !!matchNew &&
			(
				majorNew > majorOld // e.g. 2.0.0 > 1.0.0
				|| (
					majorNew === majorOld
					&& (
						minorNew > minorOld // e.g. 2.1.0 > 2.0.0
						|| (
							minorNew === minorOld
							&& (
								patchNew > patchOld // e.g. 2.1.1 > 2.1.0
								|| (
									patchNew === patchOld
									&& (
										(devNameOld !== '' && devNameNew === '') // e.g. 2.1.1 > 2.1.1-alpha || 2.1.1 > 2.1.1-alpha.1
										|| (devBuiltOld !== '' && devBuiltNew !== '' && devBuiltNew > devBuiltOld) // e.g. 2.1.1-alpha.2 > 2.1.1-alpha.1
									)
								)
							)
						)
					)
				)
			)
		this.saved[newPackage] = r
		return r
	}
}

// export const PackageVersionComparator = {
// 	isBelowThen(oldPackage: string, newPackage: string): boolean {
// 		if(cache.hasOwnProperty(oldPackage))
// 			return cache[oldPackage].isBelowThen(newPackage)
// 		else {
// 			const r = new PackageVersionComparatorImpl(oldPackage)
// 			cache[oldPackage] = r
// 			return r.isBelowThen(newPackage)
// 		}
// 	}
// }
export function PackageVersionComparator(oldPackage: string): PackageVersionComparatorImpl {
	if(cache.hasOwnProperty(oldPackage))
		return cache[oldPackage]
	else {
		const r = new PackageVersionComparatorImpl(oldPackage)
		cache[oldPackage] = r
		return r
	}
}