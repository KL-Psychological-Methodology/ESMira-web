<?php

namespace backend\noJs\pages;

use backend\Configs;
use backend\CreateDataSet;
use backend\exceptions\CriticalException;
use backend\exceptions\PageFlowException;
use Exception;
use backend\Main;
use backend\noJs\NoJsMain;
use backend\noJs\ForwardingException;
use backend\noJs\Lang;
use backend\noJs\Page;
use backend\noJs\StudyData;
use stdClass;

class AppInstall implements Page {
    /**
     * @var stdClass
     */
    private $study;
    /**
     * @var string
     */
    private $accessKey;

    // Necessary for Fallback System
    private $primaryUrl;
    private $encodedFallbackUrl;
    private $isPrimary;

    /**
     * @throws CriticalException
     * @throws PageFlowException
     * @throws ForwardingException
     */
    public function __construct() {
        if (isset($_GET['fromUrl'])) {
            $studyData = $this->setupFallback($_GET['fromUrl']);
        } else {
            $studyData = $this->setupPrimary();
        }
        $this->study = $studyData->study;
        $this->accessKey = $studyData->accessKey;
    }

    private function setupPrimary(): StudyData {
        $this->isPrimary = true;
        $this->primaryUrl = $_SERVER['HTTP_HOST'];
        $studyData = NoJsMain::getStudyData();
        if ($this->study->useFallback ?? true) {
            $fallbackUrls = Configs::getDataStore()->getFallbackTokenStore()->getOutboundTokenEncodedUrls();
            $this->encodedFallbackUrl = sizeof($fallbackUrls) > 0 ? $fallbackUrls[0] : '';
        }
        CreateDataSet::saveWebAccess($studyData->study->id, 'app_install');
        return $studyData;
    }

    private function setupFallback(string $fromUrl): StudyData {
        $this->isPrimary = false;
        $this->primaryUrl = preg_replace('/(https?:\/\/)|(\/$)/', '', base64_decode($fromUrl));
        $studyData = NoJsMain::getFallbackStudyData($fromUrl);
        $this->encodedFallbackUrl = base64_encode($_SERVER['HTTP_HOST']);
        return $studyData;
    }

    private function getDeepLinkUrl(string $scriptName): string {
        $fallbackParameter = '';
        if ($this->study->useFallback ?? true) {
            $fallbackParameter = "?fallback=" . $this->encodedFallbackUrl;
        }
        return 'esmira://' . $this->primaryUrl . $scriptName . $this->study->id . ($this->accessKey ? "-$this->accessKey" : '') . $fallbackParameter;
    }

    private function getQrCode(): string {
        if ($this->isPrimary) {
            return '<img alt="qr code" js-action="qr"/>';
        } else {
            $fallbackParameter = "?fallback=" . $this->encodedFallbackUrl;
            $protocol = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '');
            $qrUrl = $protocol . '://' . $this->primaryUrl . '/' . $this->study->id . ($this->accessKey ? "-$this->accessKey" : '') . $fallbackParameter;
            return '<img alt="qr code" js-action="directQr" qr-url="' . $qrUrl . '"/>';
        }
    }

    public function getTitle(): string {
        return $this->study->title;
    }

    public function getContent(): string {
        $installInstructions = '<ul><li>
			<div>' . Lang::get('studyTut_install_app') . '</div>'
            . (($this->study->publishedAndroid ?? true) ? '<p><a href="https://play.google.com/store/apps/details?id=at.jodlidev.esmira" target="_blank">' . Lang::get('for_AndroidSmartphones') . '</a></p>' : '')
            . (($this->study->publishedIOS ?? true) ? '<p><a href="https://apps.apple.com/gb/app/esmira/id1538774594" target="_blank">' . Lang::get('for_iOSSmartphones') . '</a></p>' : '')
            . '</li></ul>';


        $scriptName = preg_match("/^(.+\/)[^\/]+$/", $_SERVER['SCRIPT_NAME'], $matches) ? $matches[1] : 'Error';
        if (substr_compare($scriptName, 'api/', -4) == 0) //is false if it was called by fallback
            $scriptName = substr($scriptName, 0, -4);

        $output = '';

        if ($this->study->studyOver ?? false) {
            $output .= '<div class="dashRow"><div class="dashEl small highlight stretched">' . Lang::get('study_over_message') . '</div></div>';
            if (isset($this->study->postStudyNote)) {
                $output .= '<p>' . $this->study->postStudyNote . '<p>';
            }
        }

        if (isset($this->study->webInstallInstructions))
            $output .= '<div>' . $this->study->webInstallInstructions . '</div>';
        $output .= '<div class="titleRow">' . Lang::get('about_study') . '</div>';

        if (isset($this->study->studyDescription))
            $output .= '<p>' . $this->study->studyDescription . '</p>';

        $output .= '<br/>';

        if (isset($this->study->contactEmail))
            $output .= '<a class="left" href="mailto:' . $this->study->contactEmail . '">' . Lang::get('contactEmail') . '</a>';
        if (isset($this->study->informedConsentForm))
            $output .= '<a class="right" js-action="internalUrl" href="#informedConsent">' . Lang::get('informed_consent') . '</a>';

        $output .= '<br/><br/>';

        if (!$this->study->studyOver ?? false) {
            '<div class="titleRow">' . Lang::get('how_to_participate') . '</div>
		
		<div class="center">';

            if ($this->study->publishedAndroid ?? true)
                $output .= '<a style="padding: 5px;" href="https://play.google.com/store/apps/details?id=at.jodlidev.esmira" target="_blank"><img alt="Android" src="data:image/png;base64,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"/></a>';

            if ($this->study->publishedIOS ?? true)
                $output .= '<a style="padding: 5px;" href="https://apps.apple.com/gb/app/esmira/id1538774594" target="_blank"><img alt="iOS" src="data:image/png;base64,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"/></a>';

            $output .= '</div>
		<br>
		<p class="justify">' . Lang::get('studyTut_participate_description') . '</p>
		
		
		
		<div class="appInstallAlternative" js-action="clickable" click-show="alternative1">
			<h1 class="center">' . Lang::get('studyTut_participate_method_onPhone') . '</h1>
		</div>
		<ol id="alternative1" js-action="hidden">
			<li><div>
				<span>' . Lang::get('studyTut_participate_install_app') . '</span>'
                . $installInstructions
                . '</div></li>
			<li>' . Lang::get('studyTut_participate_open_this_website') . '</li>
			<li>
				<a href="' . $this->getDeepLinkUrl($scriptName) . '">' . Lang::get('studyTut_participate_click_link') . '</a>
			</li>
			<li>' . Lang::get('studyTut_participate_opens_automatically') . '</li>
		</ol>
		
		
		
		
		<h1 class="center">' . Lang::get('or') . '</h1>
		
		
		
		<div class="hidden" js-action="shown">
			<div class="appInstallAlternative" js-action="clickable" click-show="alternative2">
				<h1 class="center">' . Lang::get('studyTut_participate_method_notOnPhone') . '</h1>
			</div>
			<ol id="alternative2" js-action="hidden">
				<li><div>
					<span>' . Lang::get('studyTut_participate_install_app') . '</span>'
                . $installInstructions
                . '</div></li>
				<li>' . Lang::get('studyTut_participate_startApp') . '</li>
				
				<li>
					<div>' . Lang::get('studyTut_participate_chooseYesForQR') . '</div>
					
					<div class="screenshotBox">
						<img alt="screenshot" src="' . Lang::get('screenshot_qrCode_askYes_android') . '"/>
					</div>
				</li>
				<li>
					<span>' . Lang::get('studyTut_participate_scanQr') . '</span>
					<div class="center">' . $this->getQrCode() . '</div>
				</li>
			</ol>
			
			
			<h1 class="center">' . Lang::get('or') . '</h1>
		</div>
		
		
		
		<div class="appInstallAlternative" js-action="clickable" click-show="alternative3">
			<h1 class="center">' . Lang::get('studyTut_participate_method_manual') . '</h1>
		</div>
		<ol id="alternative3" js-action="hidden">
			<li><div>
				<span>' . Lang::get('studyTut_participate_install_app') . '</span>'
                . $installInstructions
                . '</div></li>
			
			<li>' . Lang::get('studyTut_participate_startApp') . '</li>
			
			<li>
				<div>' . Lang::get('studyTut_participate_chooseNoForQR') . '</div>
				<div class="screenshotBox">
					<img alt="screenshot" src="' . Lang::get('screenshot_qrCode_askNo_android') . '"/>
				</div>
			</li>
			
			<li>
				<div>' . Lang::get('studyTut_participate_selectServer', $_SERVER['HTTP_HOST'] . $scriptName) . '</div>
				<div class="screenshotBox">
					<img alt="screenshot" src="' . Lang::get('screenshot_server_ask_android') . '"/>
				</div>
			</li>
			
			<li><div>'
                . (
                    $this->accessKey
                    ? Lang::get('studyTut_participate_selectAccessKeyYes', $this->accessKey)
                    : Lang::get('studyTut_participate_selectAccessKeyNo')
                )
                . '</div>
			<div class="screenshotBox">
				<img alt="screenshot" src="'
                . (strlen($this->accessKey) ? Lang::get('screenshot_accessKey_askYes_android') : Lang::get('screenshot_accessKey_askNo_android'))
                . '"/>
			</div></li>
		</ol>';

            if (isset($this->study->informedConsentForm))
                $output .= '<div class="titleRow" js-action="hidden"><a id="informedConsent"></a>' . Lang::get('informed_consent') . '</div>
				<p class="justify" js-action="hidden">' . $this->study->informedConsentForm . '</p>';
        }

        return $output;
    }
}
