<?php

namespace backend;

use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\pages\AppInstall;
use backend\noJs\pages\GetParticipant;
use backend\noJs\pages\InformedConsent;
use stdClass;

class QuestionnaireSaver {
	const COOKIE_LAST_COMPLETED = 'last_completed%1$d_%2$d';
	const COOKIE_DYNAMIC_CURRENT = 'dyn%1$d_%2$d_%3$s_now';
	const COOKIE_DYNAMIC_TIME = 'dyn%1$d_%2$d_%3$s_t';
	const COOKIE_DYNAMIC_CHOICES = 'dyn%1$d_%2$d_%3$s_c';
	
	/**
	 * @var int
	 */
	var $cacheId = -1;
	/**
	 * @var bool
	 */
	var $isCompleted = false;
	/**
	 * @var stdClass
	 */
	private $study;
	/**
	 * @var stdClass
	 */
	private $questionnaire;
	/**
	 * @var int
	 */
	private $formStarted;
	/**
	 * @var int
	 */
	var $currentPageInt;
	/**
	 * @var string
	 */
	var $participant;
	
	/**
	 * @throws CriticalException|ForwardingException
	 */
	function __construct(stdClass $study, stdClass $questionnaire, bool $doForwarding = true) {
		$this->study = $study;
		$this->questionnaire = $questionnaire;
		$this->cacheId = 'questionnaire_' .($questionnaire->internalId ?? -2);
		//TODO: additionally to session, also use cookies to cache inputs
		Main::sessionStart();
		if(!isset($_SESSION[$this->cacheId]))
			$_SESSION[$this->cacheId] = [];
		
		if($doForwarding)
			$this->doForwarding();
		
		if(!isset($_SESSION[$this->cacheId]['formStarted']))
			$_SESSION[$this->cacheId]['formStarted'] = Main::getMilliseconds();
		$this->formStarted = (int) $_SESSION[$this->cacheId]['formStarted'];
		if(!isset($_SESSION[$this->cacheId]['pageStarted']))
			$_SESSION[$this->cacheId]['pageStarted'] = [];
		$this->currentPageInt = (int) ($_SESSION[$this->cacheId]['currentPage'] ?? 0);
	}
	
	private function participantIsValid($participant): bool {
		return strlen($participant) >= 1 && preg_match('/^[a-zA-Z0-9À-ž_\s\-()]+$/', $participant);
	}
	/**
	 * @throws ForwardingException
	 */
	private function setParticipant() {
		if(!isset($_POST['participant']) || !self::participantIsValid($_POST['participant']))
			throw new ForwardingException(new GetParticipant());
		
		$this->participant = $_POST['participant'];
		if(isset($_POST['new_participant'])) {
			try {
				$this->saveDataset(CreateDataSet::DATASET_TYPE_JOINED, $this->participant, false);
			}
			catch(CriticalException $e) {
				$this->errorMsg = $e->getMessage();
			}
		}
		
		$studyId = $this->study->id;
		Main::setCookie("participant$studyId", $this->participant);
	}
	
	/**
	 * @throws ForwardingException
	 */
	private function doForwarding() {
		$studyId = $this->study->id;
		
		if(isset($this->study->publishedWeb) && !$this->study->publishedWeb)
			throw new ForwardingException(new AppInstall());
		else if(isset($this->study->informedConsentForm) && strlen($this->study->informedConsentForm) && !isset($_COOKIE["informed_consent$studyId"])) {
			if(!isset($_POST['informed_consent']))
				throw new ForwardingException(new InformedConsent());
			else
				Main::setCookie("informed_consent$studyId", '1');
		}
		
		if(!isset($_COOKIE["participant$studyId"]) || !self::participantIsValid($_COOKIE["participant$studyId"]))
			$this->setParticipant();
		else
			$this->participant = $_COOKIE["participant$studyId"];
	}
	
	function getSessionUrlParameter(): string {
		return htmlspecialchars(SID);
	}
	
	
	function saveCache(string $key, string $value) { //public for testing
		$_SESSION[$this->cacheId]['responses'][$key] = $value;
	}
	private function getCache(string $key): ?string {
		return $_SESSION[$this->cacheId]['responses'][$key] ?? null;
	}
	function deleteCache() {
		unset($_SESSION[$this->cacheId]);
	}
	private function getCacheResponses(): array {
		return $_SESSION[$this->cacheId]['responses'] ?? [];
	}
	private function saveAdditionalValues(stdClass $input, array $newResponses) {
		switch($input->responseType ?? 'text') {
			case "dynamic_input":
				$_SESSION[$this->cacheId]['responses']["$input->name~index"] = $newResponses["$input->name~index"] ?? -1;
				break;
			case "list_multiple":
				$subResponses = $newResponses[$input->name] ?? [];
				$i = 1;
				foreach($input->listChoices as $choice) {
					$_SESSION[$this->cacheId]['responses']["$input->name~$i"] = in_array($choice, $subResponses);
					$i++;
				}
				break;
			default:
				break;
		}
	}
	private function extractValue(stdClass $input, array $responses): string {
		$value = $responses[$input->name] ?? '';
		switch($input->responseType ?? '') {
			case 'time':
				if(isset($input->forceInt) && $input->forceInt) {
					$split = explode(':', $value);
					if(sizeof($split) == 2)
						return $split[0] * 60 + $split[1];
				}
				return $value;
			case 'list_multiple':
				if(is_array($value))
					return implode(',', $value);
				else
					return $value;
			default:
				return $value;
		}
	}
	
	function setPage($i, $movedToNext = false) {
		$i = min($i, count($this->questionnaire->pages));
		$i = max($i, 0);
		$this->currentPageInt = $i;
		$_SESSION[$this->cacheId]['currentPage'] = $i;
		
		if($movedToNext)
			$_SESSION[$this->cacheId]['pageStarted'][$i-1] = Main::getMilliseconds();
	}
	function unsetPage() {
		unset($_SESSION[$this->cacheId]['currentPage']);
	}
	public function getTitle(): string {
		$pagesSize = sizeof($this->questionnaire->pages);
		$output = $this->questionnaire->title ?? Lang::get('questionnaire');
		if($pagesSize > 1)
			$output .= ' (' .($this->currentPageInt+1).'/'.$pagesSize.')';
		return $output;
	}
	
	/**
	 * @param stdClass $page
	 * @param int $studyId only used for dynamic input
	 * @param int $questionnaireId only used for dynamic input
	 * @return string
	 */
	function drawPage(): string {
		if(!isset($_POST) || (!isset($_POST['save']) && !isset($_POST['continue']))) {
			CreateDataSet::saveWebAccess($this->study->id, 'questionnaire ' .$this->questionnaire->internalId);
		}
		
		$page = $this->questionnaire->pages[$this->currentPageInt];
		$inputs = $page->inputs;
		if($page->randomized ?? false)
			shuffle($inputs);
		
		$output = '<form class="questionnaireBox coloredLines" method="post">';
		
		if(isset($page->header))
		    $output .= "<div class=\"line horizontalPadding verticalPadding\">$page->header</div>";

		$anyRequiredInputs = false;
		foreach($inputs as $input) {
			if(!isset($input->name))
				continue;
			$output .= $this->drawInput($input);
			if($input->required ?? false) {
				$anyRequiredInputs = true;
			}
		}
		
		if(isset($page->footer))
			$output .= "<div class=\"line horizontalPadding verticalPadding\">$page->footer</div>";
		
		$output .= '<input type="hidden" name="participant" value="' .$this->participant .'"/>
			<input type="hidden" name="informed_consent" value="1"/>'
			.($anyRequiredInputs ? '<p class="smallText spacingTop">'.Lang::get('info_required').'</p>' : '')
			.($this->currentPageInt > 0
				? '<input type="submit" id="pagePrevious" name="previous" class="left" value="'.Lang::get('previous').'"/>'
				: ''
			)
			.(($this->currentPageInt == count($this->questionnaire->pages) -1)
				? '<input type="submit" id="pageSave" name="save" class="right" value="' .Lang::get('save').'"/>'
				: '<input type="submit" id="pageContinue" name="continue" class="right" value="'.Lang::get('continue').'"/>')
			.'
		</form>';
		
		return $output;
	}
	
	/**
	 * @param stdClass $input
	 * @return string
	 */
	function drawInput(stdClass &$input): string {
		$responseType = $input->responseType ?? 'text_input';
		if($this->isSkipped($responseType))
			return '';
		
		$name = 'responses['.$input->name.']';
		$value = $this->getCache($input->name) ?? ($input->defaultValue ?? '');
		$required = $input->required ?? false;
		
		if(!method_exists($this, $responseType))
			$output = $this->error($input);
		else
			$output = $this->{$responseType}($input, $required, $name, $value, $this->study->id, $this->questionnaire->internalId);
		
		return "<div class=\"line horizontalPadding verticalPadding\" id=\"item-$input->name\">$output</div>";
	}
	
	function isSkipped($responseType): bool {
		switch($responseType) {
			case "app_usage":
			case "countdown":
			case "compass":
			case "photo":
				return true;
			default:
				return false;
		}
	}
	
	/**
	 * @throws PageFlowException
	 */
	private function extractInputs(): ?stdClass {
		$pages = $this->questionnaire->pages;
		$pageBefore = $this->currentPageInt;
		
		if(!isset($pages[$pageBefore]))
			throw new PageFlowException(Lang::get('error_unknown_data'));
		
		$inputsBefore = $pages[$pageBefore]->inputs;
		
		$responses = $_POST['responses'] ?? [];
		
		$missingInput = null;
		foreach($inputsBefore as $input) {
			if(!isset($input->name) || $this->isSkipped($input->responseType ?? 'text'))
				continue;
			$value = $this->extractValue($input, $responses);
			if($missingInput == null && ($input->required ?? false) && $value == '')
				$missingInput = $input;
			
			$this->saveCache($input->name, $value);
			$this->saveAdditionalValues($input, $responses);
		}
		return $missingInput;
	}
	
	function finishActionNeeded(): bool {
		return isset($_POST['previous']) || isset($_POST['continue']) || isset($_POST['save']);
	}
	/**
	 * @throws PageFlowException
	 * @throws CriticalException
	 */
	function doPageFinishActions(string $appType = "Web", bool $canSave = true): ?stdClass {
		$pageBefore = $this->currentPageInt;
		
		$missingInput = $this->extractInputs(); //also caches values
		
		if(isset($_POST['previous'])) {
			$this->setPage($pageBefore - 1);
			return null; //we dont care about missing inputs
		}
		
		if($missingInput != null)
			return $missingInput;
		
		if(isset($_POST['continue'])) {
			$this->setPage($pageBefore + 1, true);
		}
		else if($canSave && isset($_POST['save'])) {
			$now = Main::getMilliseconds();
			$pageStarted = $_SESSION[$this->cacheId]['pageStarted'];
			$pageDurations = [];
			$last = $this->formStarted;
			foreach($pageStarted as $current) {
				$pageDurations[] = $current - $last;
				$last = $current;
			}
			$pageDurations[] = $now - $last;
			
			$this->saveCache('formDuration', $now - $this->formStarted); //used in CreateDataSet
			$this->saveCache('pageDurations', implode(",", $pageDurations)); //used in CreateDataSet
			$this->saveDataset(CreateDataSet::DATASET_TYPE_QUESTIONNAIRE, $this->participant, true, $appType);
			
			$lastCompletedCookieName = sprintf(self::COOKIE_LAST_COMPLETED, $this->study->id, $this->questionnaire->internalId);
			Main::setCookie($lastCompletedCookieName, time());
			
			$this->deleteCache();
			$this->unsetPage();
			
			$this->isCompleted = true;
		}
		
		return null;
	}
	
	/**
	 * @throws CriticalException
	 */
	function saveDataset(string $type, string $userId, bool $fromQuestionnaire, string $appType = "Web") {
		$accessKey = Main::getAccessKey();
		
		$responses = (object)[
			'model' => $_SERVER['HTTP_USER_AGENT'] ?? ''
		];
		if($fromQuestionnaire) {
			foreach($this->getCacheResponses() as $key => $value) {
				$responses->{$key} = $value;
			}
		}
		$json = (object)[
			'userId' => $userId,
			'appType' => $appType,
			'appVersion' => (string) Main::SERVER_VERSION,
			'serverVersion' => Main::SERVER_VERSION,
			'dataset' => [(object)[
				'dataSetId' => 0,
				'studyId' => $this->study->id,
				'studyVersion' => $this->study->version ?? 0,
				'studySubVersion' => $this->study->subVersion ?? 0,
				'studyLang' => $this->study->lang ?? '',
				'accessKey' => ($accessKey) ?: '',
				'questionnaireName' => $fromQuestionnaire ? $this->questionnaire->title : null,
				'questionnaireInternalId' => $fromQuestionnaire ? $this->questionnaire->internalId : null,
				'eventType' => $type,
				'responseTime' => Main::getMilliseconds(),
				'responses' => $responses
			]],
		];
		
		$dataSet = new CreateDataSet();
		$dataSet->prepare($json);
		$dataSet->exec();
		if(empty($dataSet->output))
			throw new CriticalException('No response data');
		else if(!$dataSet->output[0]['success'])
			throw new CriticalException($dataSet->output[0]['error']);
	}
	
	
	function error(stdClass $input): string {
		$responseType = $input->responseType ?? 'Error';
		return "<div class=\"highlight center\">Broken Input ($responseType)</div>";
	}
	
	function text(stdClass $input, bool $required, string $name, string $value): string {
		$text = $input->text ?? '';
		$output = $text;
		if($required && strlen($text))
			$output .= '*';
		return "$output<br/>";
	}
	
	function binary(stdClass $input, bool $required, string $name, string $value): string {
		$leftSideLabel = $input->leftSideLabel ?? '';
		$rightSideLabel = $input->rightSideLabel ?? '';
		if($value == '0') {
			$leftChecked = 'checked="checked"';
			$rightChecked = '';
		}
		else if($value == '1') {
			$leftChecked = '';
			$rightChecked = 'checked="checked"';
		}
		else {
			$leftChecked = '';
			$rightChecked = '';
		}
		$requiredMarker = $required ? 'required="required"' : '';
		
		return $this->text($input, $required, $name, $value)
			."<div class=\"listParent\">
				<div class=\"listChild center\">&nbsp;
					<label class=\"left noDesc noTitle\">
						$leftSideLabel
						<input type=\"radio\" name=\"$name\" value=\"0\" $leftChecked $requiredMarker/>
					</label>
					<label class=\"right noDesc noTitle\">
						<input type=\"radio\" name=\"$name\" value=\"1\" $rightChecked $requiredMarker/>
						$rightSideLabel
					</label>
				</div>
			</div>";
	}
	
	function date(stdClass $input, bool $required, string $name, string $value): string {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"date\" $requiredMarker/></div>";
	}
	
	function dynamic_input(stdClass $input, bool $required, string $name, string $value, int $studyId, int $questionnaireId): string {
		$inputName = $input->name ?? 'name';
		$lastCompletedCookieName = sprintf(self::COOKIE_LAST_COMPLETED, $studyId, $questionnaireId);
		$dynamicCurrentCookieName = sprintf(self::COOKIE_DYNAMIC_CURRENT, $studyId, $questionnaireId, $inputName);
		$dynamicTimeCookieName = sprintf(self::COOKIE_DYNAMIC_TIME, $studyId, $questionnaireId, $inputName);
		$lastCompleted = $_COOKIE[$lastCompletedCookieName] ?? 0;
		
		$dynamicI = $_COOKIE[$dynamicCurrentCookieName] ?? -1;
		$choices = $input->subInputs ?? [];
		
		if(!isset($_COOKIE[$dynamicTimeCookieName]) || $_COOKIE[$dynamicTimeCookieName] <= $lastCompleted || $dynamicI < 0 || $dynamicI >= sizeof($choices)) {
			if(isset($input->random) && $input->random) {
				$dynamicChoicesCookieName = sprintf(self::COOKIE_DYNAMIC_CHOICES, $studyId, $questionnaireId, $inputName);
				$leftChoicesIndex = json_decode($_COOKIE[$dynamicChoicesCookieName] ?? '[]');
				
				if($dynamicI >= 0) { //remove option from last filled out questionnaire
					$i = array_search($dynamicI, $leftChoicesIndex);
					if($i !== false)
						array_splice($leftChoicesIndex, $i, 1);
				}
				
				if(!sizeof($leftChoicesIndex)) {
					for($i = sizeof($choices) - 1; $i >= 0; --$i) {
						$leftChoicesIndex[] = $i;
					}
				}
				Main::setCookie($dynamicChoicesCookieName, json_encode($leftChoicesIndex));
				$dynamicI = $leftChoicesIndex[rand(0, sizeof($leftChoicesIndex) - 1)];
			}
			else if($dynamicI < 0 || ++$dynamicI >= sizeof($choices))
				$dynamicI = 0;
			
			Main::setCookie($dynamicCurrentCookieName, $dynamicI);
			Main::setCookie($dynamicTimeCookieName, time());
		}
		
		if(!isset($choices[$dynamicI]))
			return $this->error($input);
		
		$element = clone $choices[$dynamicI];
		
		if(isset($element->required)) {
			$required = $element->required;
			if($element->required && isset($input->text) && strlen($input->text)) {
				$element->required = false; //we dont want to display the required marker twice
			}
		}
		$element->name = $input->name;
		
		return $this->text($input, $required, $name, $value)
			.'<div>
				<div>'. $this->drawInput($element, $studyId, $questionnaireId) .'</div>
				<input type="hidden" name="responses['.$input->name."~index]" .'" value="'.($dynamicI+1).'"/>
			</div>';
	}
	
	function image(stdClass $input, bool $required, string $name, string $value): string {
		$url = $input->url ?? '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><img alt=\"\" src=\"$url\" class=\"questionnaireInputImage\"/><input type=\"hidden\" name=\"$name\" value=\"1\"/></div>";
	}
	
	function likert(stdClass $input, bool $required, string $name, string $value): string {
		$leftSideLabel = $input->leftSideLabel ?? '';
		$rightSideLabel = $input->rightSideLabel ?? '';
		$requiredMarker = $required ? 'required="required"' : '';
		
		$radioBoxes = '';
		for($i=1, $max=isset($input->likertSteps) ? $input->likertSteps+1 : 6; $i<$max; ++$i) {
			$radioBoxes .= "<input type=\"radio\" name=\"$name\" value=\"$i\" $requiredMarker ".($value==$i ? 'checked="checked"' : '') .'/>';
		}
		
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\">
			<div>&nbsp;
				<div class=\"left smallText\">$leftSideLabel</div>
				<div class=\"right smallText\">$rightSideLabel</div>
			</div>
			<div>
				$radioBoxes
			</div>
		</div>";
	}
	
	function list_multiple(stdClass $input, bool $required, string $name, string $value): string {
		$radioBoxes = '';
		foreach($input->listChoices ?? [] as $v) {
			$radioBoxes .= '<label class="noTitle noDesc vertical"><input class="horizontal" type="checkbox" name="'.$name.'[]" value="'.$v.'" ' .(strpos($value, $v) !== false ? 'checked="checked"' : '') .'/><span>'.$v.'</span></label>';
		}
		return $this->text($input, $required, $name, $value)
			."<div class=\"listParent\">
			<div class=\"listChild\">
				$radioBoxes
			</div>
		</div>";
	}
	
	function list_single(stdClass $input, bool $required, string $name, string $value): string {
		$output = $this->text($input, $required, $name, $value);
		$requiredMarker = $required ? 'required="required"' : '';
		
		if(isset($input->asDropDown) && !$input->asDropDown) {
			$output .= '<div class="listParent"><div class="listChild center">';
			foreach($input->listChoices as $choiceValue) {
				$output .= "<label class=\"vertical noDesc noTitle\"><input type=\"radio\" name=\"$name\" value=\"$choiceValue\" ".($choiceValue==$value ? 'checked="checked"' : '') ."$requiredMarker/>
					<span>$choiceValue</span>
					</label>";
			}
			$output .= '</div></div>';
		}
		else {
			//optgroup is added as a workaround for IOS: https://stackoverflow.com/questions/19011978/ios-7-doesnt-show-more-than-one-line-when-option-is-longer-than-screen-size
			
			$output .= "<div class=\"center\"><select name=\"$name\" $requiredMarker\"><option value=\"\">" .Lang::get('please_select') .'</option>';
			foreach ($input->listChoices ?? [] as $v) {
				$output .= '<option' .($value == $v ? ' selected="selected"' : '') .">$v</option>";
			}
			$output .= '<optgroup label=""></optgroup></select></div>';
		}
		
		return $output;
	}
	function number(stdClass $input, bool $required, string $name, string $value): string {
		$step = isset($input->numberHasDecimal) && $input->numberHasDecimal ? '0.5' : '1';
		$requiredMarker = $required ? 'required="required"' : '';
		
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"number\" step=\"$step\" style=\"width: 100px;\" $requiredMarker/></div>";
	}
	
	function text_input(stdClass $input, bool $required, string $name, string $value): string {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><textarea name=\"$name\" $requiredMarker>$value</textarea></div>";
	}
	
	function time(stdClass $input, bool $required, string $name, string $value): string {
		$requiredMarker = $required ? 'required="required"' : '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><input name=\"$name\" value=\"$value\" type=\"time\" $requiredMarker/></div>";
	}
	
	function va_scale(stdClass $input, bool $required, string $name, string $value): string {
		$leftSideLabel = $input->leftSideLabel ?? '';
		$rightSideLabel = $input->rightSideLabel ?? '';
		$requiredMarker = $required ? 'required="required"' : '';
		$noValue = $value == "";
		$maxValue = (isset($input->maxValue) && $input->maxValue > 1) ? $input->maxValue : 100;
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\">&nbsp;
			<div class=\"left smallText\">$leftSideLabel</div>
			<div class=\"right smallText\">$rightSideLabel</div>
		</div>
		<div class=\"center\">
			<div></div>
			<input " .($noValue ? 'no-value="1"' : '') ." name=\"$name\" value=\"" .($noValue ? ($maxValue/2) : $value) ."\" type=\"range\" min=\"1\" max=\"$maxValue\" $requiredMarker/>
		</div>";
	}
	
	function video(stdClass $input, bool $required, string $name, string $value): string {
		$url = $input->url ?? '';
		return $this->text($input, $required, $name, $value)
			."<div class=\"center\"><iframe src=\"$url\"></iframe></div><input type=\"hidden\" name=\"$name\" value=\"1\"/>";
	}
}