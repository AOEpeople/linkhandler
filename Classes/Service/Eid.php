<?php
namespace AOE\Linkhandler\Service;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
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

/**
 * eID script
 *
 * @author Michael Klapper <klapper@aoemedia.de>
 * @package Linkhandler
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
	 * The Value is seperated by ":"#
	 * - Workspace ID
	 * - Backend user ID
	 * - Time to live for the WS link
	 *
	 * @example 1:5:172800
	 * @var string|null
	 */
	protected $WSPreviewValue = NULL;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$authCode = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('authCode');
		$linkParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('linkParams');
		$this->languageId = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L');

			// extract the linkhandler and WS preview prameter
		if ( strpos($linkParams, ';') > 0) {
			list ($this->linkHandlerParams, $this->WSPreviewValue)  = explode(';', $linkParams);
			$this->isWsPreview = TRUE;
		} else {
			$this->linkHandlerParams = $linkParams;
		}

		list($this->linkHandlerKeyword) = explode(':', $this->linkHandlerParams);
		$this->linkHandlerValue = str_replace($this->linkHandlerKeyword . ':', '', $this->linkHandlerParams);

		// check the authCode
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::stdAuthCode($linkParams . $this->languageId, '', 32) !== $authCode ) {
			header('HTTP/1.0 401 Access denied.');
			exit('Access denied.');
		}

		$this->initTSFE();
	}

	/**
	 * Initializes tslib_fe and sets it to $GLOBALS['TSFE']
	 *
	 * @return	void
	 */
	protected function initTSFE() {
		\TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();

		$pid = \TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
		$GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $pid, 0, 0, 0);

		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser(); //!TODO first check if already a fe_user session exists - otherwise this line will overwrite the existing one
		$GLOBALS['TSFE']->checkAlternativeIdMethods();

		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();
		$GLOBALS['TSFE']->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
	}

	/**
	 * @example ?eID=linkhandlerPreview&linkParams=record:tx_aoetirepresenter_tire:40&id=23
	 * @return void
	 */
	public function process() {
		$typoLinkSettingsArray = array (
			'returnLast' => 'url',
			'additionalParams' => '&L=' . $this->languageId
		);

		// if we need a WS preview link we need to disable the realUrl and simulateStaticDocuments
		if ($this->isWsPreview === TRUE) {
			$GLOBALS['TSFE']->config['config']['tx_realurl_enable'] = 0;
			$GLOBALS['TSFE']->config['config']['simulateStaticDocuments'] = 0;
		}

		$linkhandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('AOE\Linkhandler\Handler'); /** @var \AOE\Linkhandler\Handler $linkhandler */

		$linkString = $linkhandler->main (
			'',
			$typoLinkSettingsArray,
			$this->linkHandlerKeyword,
			$this->linkHandlerValue,
			$this->linkHandlerParams,
			$GLOBALS['TSFE']->cObj
		);

		if ($this->isWsPreview === TRUE) {
			list ($wsId, $userId, $timeToLive) = explode(':', $this->WSPreviewValue);

			$queryString = 'index.php?ADMCMD_prev=' . \TYPO3\CMS\Backend\Utility\BackendUtility::compilePreviewKeyword (
				str_replace('index.php?', '', $GLOBALS['TSFE']->cObj->lastTypoLinkLD['totalURL']) . '&ADMCMD_previewWS=' . $wsId,
				$userId,
				$timeToLive
			);
		} else {
			$queryString = $linkString;
		}

		$fullUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $queryString;

		header('Location: ' . $fullUrl);
		exit();
	}
}

/** @var \AOE\Linkhandler\Service\Eid $linkhandlerService */
$linkhandlerService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('AOE\Linkhandler\Service\Eid');
$linkhandlerService->process();
