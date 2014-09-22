<?php
namespace AOE\Linkhandler\Service;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, AOE GmbH <dev@aoe.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * eID script
 *
 * @author  Michael Klapper <michael.klapper@aoe.com>
 * @author  Chetan Thapliyal <chetan.thapliyal@aoe.com>
 * @package AOE\Linkhandler\Service
 */
class Eid {

	/**
	 * @example "record:tt_news:2"
	 * @var string
	 */
	protected $linkHandlerParams = '';

	/**
	 * @example "tt_news:2"
	 * @var string
	 */
	protected $linkHandlerValue = '';

	/**
	 * Keyword like "record"
	 *
	 * @example $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record']
	 * @var string
	 */
	protected $linkHandlerKeyword = '';

	/**
	 * Indicate that the current request is from any WS
	 *
	 * @var boolean
	 */
	protected $isWsPreview = FALSE;

	/**
	 * sys_language_uid
	 *
	 * @var integer
	 */
	protected $languageId = 0;

	/**
	 * Contains all required values to build an WS preview link.
	 *
	 * The Value is separated by ":"#
	 * - Workspace ID
	 * - Backend user ID
	 * - Time to live for the WS link
	 *
	 * @example 1:5:172800
	 * @var string|null
	 */
	protected $WSPreviewValue = NULL;

	/**
	 * @var array
	 */
	protected $typoLinkSettings = array();


	/**
	 * Initializes class instance.
	 */
	public function initialize() {
		$authCode = (string) GeneralUtility::_GP('authCode');
		$linkParams = GeneralUtility::_GP('linkParams');
		$this->languageId = (int)GeneralUtility::_GP('L');

		$this->validateAuthCode($authCode, $linkParams);

		$this->typoLinkSettings = array (
			'returnLast' => 'url',
			'additionalParams' => '&L=' . $this->languageId
		);

		$this->initTSFE();

			// extract the linkhandler and WS preview parameters
		if (strpos($linkParams, ';') > 0) {
			list($this->linkHandlerParams, $this->WSPreviewValue) = explode(';', $linkParams);
			$this->initializeWorkspacePreviewContext();
		} else {
			$this->linkHandlerParams = $linkParams;
		}

		list($this->linkHandlerKeyword) = explode(':', $this->linkHandlerParams);
		$this->linkHandlerValue = str_replace($this->linkHandlerKeyword . ':', '', $this->linkHandlerParams);
	}

	/**
	 * @param  string $authCode
	 * @param  string $linkParams
	 */
	private function validateAuthCode($authCode, $linkParams) {
		$expectedAuthCode = GeneralUtility::stdAuthCode($linkParams . $this->languageId, '', 32);
		if ($expectedAuthCode !== $authCode) {
			header('HTTP/1.0 401 Access denied.');
			exit('Access denied.');
		}
	}

	/**
	 * Initializes workspace preview context.
	 */
	private function initializeWorkspacePreviewContext() {
		$this->isWsPreview = TRUE;

			// disable realUrl and simulateStaticDocuments
		$GLOBALS['TSFE']->config['config']['tx_realurl_enable'] = 0;
		$GLOBALS['TSFE']->config['config']['simulateStaticDocuments'] = 0;

		$workspaceId = strstr($this->WSPreviewValue, ':', true);
		$this->typoLinkSettings['additionalParams'] .= '&ADMCMD_previewWS=' . $workspaceId;
	}

	/**
	 * Initializes tslib_fe and sets it to $GLOBALS['TSFE']
	 *
	 * @return	void
	 */
	protected function initTSFE() {
		\TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();

		$pid = \TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger(GeneralUtility::_GP('id'));
		$GLOBALS['TSFE'] = GeneralUtility::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $pid, 0, 0, 0);

		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser(); //!TODO first check if already a fe_user session exists - otherwise this line will overwrite the existing one
		$GLOBALS['TSFE']->checkAlternativeIdMethods();

		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();
		$GLOBALS['TSFE']->cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
	}

	/**
	 * @example ?eID=linkhandlerPreview&linkParams=record:tx_aoetirepresenter_tire:40&id=23
	 * @return void
	 */
	public function process() {

		/** @var \AOE\Linkhandler\Handler $linkhandler */
		$linkhandler = GeneralUtility::makeInstance('AOE\Linkhandler\Handler');

		$linkString = $linkhandler->main(
			'',
			$this->typoLinkSettings,
			$this->linkHandlerKeyword,
			$this->linkHandlerValue,
			$this->linkHandlerParams,
			$GLOBALS['TSFE']->cObj
		);

		$queryString =  $this->isWsPreview
						? $this->generateWorkspacePreviewUri($GLOBALS['TSFE']->cObj->lastTypoLinkLD['totalURL'])
						: $linkString;

		$fullUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $queryString;

		header('Location: ' . $fullUrl);
		exit();
	}

	/**
	 * @param  string $uri
	 * @return string
	 */
	private function generateWorkspacePreviewUri($uri) {
		list (, $userId, $timeToLive) = explode(':', $this->WSPreviewValue);

		/** @var \TYPO3\CMS\Version\Hook\PreviewHook $previewObject */
		$previewObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Hook\\PreviewHook');

		$uri = 'index.php?ADMCMD_prev=' . $previewObject->compilePreviewKeyword(
			str_replace('index.php?', '', $uri),
			$userId,
			$timeToLive
		);

		return $uri;
	}
}

/** @var \AOE\Linkhandler\Service\Eid $linkhandlerService */
$linkhandlerService = GeneralUtility::makeInstance('AOE\Linkhandler\Service\Eid');
$linkhandlerService->initialize();
$linkhandlerService->process();
