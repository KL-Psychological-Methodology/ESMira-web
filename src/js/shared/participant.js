import {check_string} from "../helpers/basics";

export function participant_isValid(p) {
	return p.length >= 1 && check_string(p);
	// return p.match(/^.{4}-.{4}-.{4}$/) != null;
}