import {ObservableStructure} from "../../observable/ObservableStructure";

export type InputResponseType =
	"app_usage" |
	"binary" |
	"bluetooth_devices" |
	"compass" |
	"countdown" |
	"date" |
	"duration" |
	"dynamic_input" |
	"file_upload" |
	"image" |
	"likert" |
	"list_multiple" |
	"list_single" |
	"location" |
	"number" |
	"photo" |
	"record_audio" |
	"share" |
	"text" |
	"text_input" |
	"time" |
	"va_scale" |
	"video"

export type InputMediaTypes = "image" | "audio"

export class Input extends ObservableStructure {
	public responseType				= this.primitive<InputResponseType>("responseType",		"text_input")
	public name						= this.primitive<string>(			"name",				"input")
	public required					= this.primitive<boolean>(			"required",			false)
	public random					= this.primitive<boolean>(			"random",			false)
	public likertSteps				= this.primitive<number>(			"likertSteps",		5)
	public numberHasDecimal			= this.primitive<boolean>(			"numberHasDecimal",	false)
	public asDropDown				= this.primitive<boolean>(			"asDropDown",		true)
	public forceInt					= this.primitive<boolean>(			"forceInt",			false)
	public packageId				= this.primitive<string>(			"packageId",		"")
	public timeoutSec				= this.primitive<number>(			"timeoutSec",		0)
	public playSound				= this.primitive<boolean>(			"playSound",		false)
	public showValue				= this.primitive<boolean>(			"showValue",		false)
	public maxValue					= this.primitive<number>(			"maxValue",			0)
	public resolution				= this.primitive<number>(			"resolution",		0)
	public quality					= this.primitive<number>(			"quality",			100)
	public relevance				= this.primitive<string>(			"relevance",		"")
	public vertical					= this.primitive<boolean>(			"vertical",			false)
	public textScript				= this.primitive<string>(			"textScript",		"")
	public other					= this.primitive<boolean>(			"other", 			false)
	
	public defaultValue				= this.translatable(				"defaultValue",		"")
	public text						= this.translatable(				"text",				"")
	public url						= this.translatable(				"url",				"")
	public leftSideLabel			= this.translatable(				"leftSideLabel", 	"")
	public rightSideLabel			= this.translatable(				"rightSideLabel",	"")
	public listChoices				= this.translatableArray(			"listChoices")
	
	public subInputs				= this.objectArray(				"subInputs", Input)
	
	
	public getMediaType(): InputMediaTypes | null {
		switch(this.responseType.get()) {
			case "file_upload":
			case "photo":
				return "image";
			case "record_audio":
				return "audio";
			default:
				return null;
		}
	}
}