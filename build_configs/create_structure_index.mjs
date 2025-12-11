#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";
import pathDefaults from "./paths.js";


const fileList = fs.readdirSync(path.resolve(pathDefaults.DIST));
const structureIndex = fileList.indexOf("STRUCTURE"); // might exist in a dev environment
const dataIndex = fileList.indexOf("esmira_data"); // might exist in a dev environment
const maintenanceIndex = fileList.indexOf(".maintenanceLock"); // will exist after downgrading in dev to a pre-maintenance feature version
if(structureIndex !== -1) {
	fileList.splice(structureIndex, 1);
}
if(dataIndex !== -1) {
	fileList.splice(dataIndex, 1);
}
if(maintenanceIndex !== -1) {
	fileList.splice(maintenanceIndex, 1);
}

const structureArray = ["STRUCTURE", ...fileList]; //We want STRUCTURE first, to make it easier to recover if UpdateStepReplace fails
const structure = JSON.stringify(structureArray);
fs.writeFile(
	path.resolve(pathDefaults.DIST, "STRUCTURE"),
	structure,
	err => {
		if(err) {
			console.error(err);
		}
		else {
			console.log(`Created STRUCTURE file for ${pathDefaults.DIST}`);
		}
	}
);
