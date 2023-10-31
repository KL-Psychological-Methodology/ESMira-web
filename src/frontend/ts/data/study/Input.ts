import {TranslatableObject} from "../../observable/TranslatableObject";

export type InputResponseType =
	"app_usage" |
	"binary" |
	"bluetooth_devices" |
	"compass" |
	"countdown" |
	"date" |
	"dynamic_input" |
	"file_upload" |
	"image" |
	"likert" |
	"list_multiple" |
	"list_single" |
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

export class Input extends TranslatableObject {
	public responseType				= this.primitive<InputResponseType>("responseType",		"text_input")
	public name						= this.primitive<string>(		"name",					"input")
	public required					= this.primitive<boolean>(		"required",				false)
	public random					= this.primitive<boolean>(		"random",					false)
	public likertSteps				= this.primitive<number>(		"likertSteps",				5)
	public numberHasDecimal			= this.primitive<boolean>(		"numberHasDecimal",		false)
	public asDropDown				= this.primitive<boolean>(		"asDropDown",				true)
	public forceInt					= this.primitive<boolean>(		"forceInt",				false)
	public packageId				= this.primitive<string>(		"packageId",				"")
	public timeoutSec				= this.primitive<number>(		"timeoutSec",				0)
	public playSound				= this.primitive<number>(		"playSound",				0)
	public showValue				= this.primitive<boolean>(		"showValue",				false)
	public maxValue					= this.primitive<number>(		"maxValue",				0)
	
	public defaultValue				= this.translatable(			"defaultValue",			"")
	public text						= this.translatable(			"text",					"")
	public url						= this.translatable(			"url",						"")
	public leftSideLabel			= this.translatable(			"leftSideLabel", 			"")
	public rightSideLabel			= this.translatable(			"rightSideLabel",			"")
	public listChoices				= this.translatableArray(		"listChoices")
	
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