import {CompatibilityCheck} from "./site/CompatibilityCheck";

const check = new CompatibilityCheck()
if(check.isCompatible())
	check.toggleUrl();