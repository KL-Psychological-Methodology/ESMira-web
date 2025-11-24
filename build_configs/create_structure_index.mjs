#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";
import pathDefaults from "./paths.js";

const target = process.argv[2] ?? "DIST";
const targetPath = pathDefaults[target];

const structureArray = ["STRUCTURE", ...fs.readdirSync(path.resolve(targetPath))]; //We want STRUCTURE first, to make it easier to recover if UpdateStepReplace fails
const structure = JSON.stringify(structureArray);
fs.writeFile(
	path.resolve(pathDefaults.DIST, "STRUCTURE"),
	structure,
	err => {
		if(err) {
			console.error(err);
		}
		else {
			console.log(`Created STRUCTURE file for ${targetPath}`);
		}
	}
);
