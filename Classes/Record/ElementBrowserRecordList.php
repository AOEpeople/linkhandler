<?php
namespace AOE\Linkhandler\Record;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel Pötzinger <daniel.poetzinger@aoemedia.de>
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
 * Extend ElementBrowserRecordList to fix linkWraps for RTE link browser
 *
 * @author Daniel Poetzinger (AOE media GmbH)
 * @package Linkhandler
 */
class ElementBrowserRecordList extends \TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList {

	/**
	 * @var string
	 */
	protected $addPassOnParameters;

	/**
	 * @var string
	 */
	protected $linkHandler = 'record';

	/**
	 * Set the parameters that should be added to the link, in order to keep the required vars for the linkwizard
	 * @param string $addPassOnParameters
	 * @return void
	 */
	public function setAddPassOnParameters($addPassOnParameters) {
		$this->addPassOnParameters = $addPassOnParameters;
	}

	/**
	 * Override the default linkhandler
	 *
	 * @param string $linkHandler
	 * @return void
	 */
	public function setOverwriteLinkHandler($linkHandler) {
		$this->linkHandler = $linkHandler;
	}

	/**
	 * Returns the title of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record)
	 *
	 * @param string $table Table name
	 * @param integer $uid UID
	 * @param string $title Title string
	 * @param array $row Records array (from table name)
	 * @return string
	 */
	public function linkWrapItems($table, $uid, $title, $row) {
		// if we handle translation records, make sure that we refer to the localisation parent with their uid
		if (is_array($GLOBALS['TCA'][$table]['ctrl']) && array_key_exists('transOrigPointerField', $GLOBALS['TCA'][$table]['ctrl']) ) {
			$transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];

			if (\TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger($row[$transOrigPointerField]) > 0) {
				$uid = $row[$transOrigPointerField];
			}
		}

		$currentImage = '';
		if ($this->browselistObj->curUrlInfo['recordTable'] === $table && $this->browselistObj->curUrlInfo['recordUid'] === $uid) {
			$currentImage = '<img' .
				\TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_right.gif', 'width="5" height="9"') .
				' class="c-blinkArrowL" alt="" />';
		}

		$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row, FALSE, TRUE);

		if (@$this->browselistObj->mode === 'rte') {
			//used in RTE mode:
			$aOnClick = 'return link_spec(\'' . $this->linkHandler . ':' . $table . ':' . $uid . '\');';
		} else {
			//used in wizard mode
			$aOnClick = 'return link_folder(\'' . $this->linkHandler . ':' . $table . ':' . $uid . '\');';
		}

		return '<a href="#" onclick="' . $aOnClick . '">' . $title . $currentImage . '</a>';
	}

	/**
	 * Returns additional, local GET parameters to include in the links of the record list.
	 *
	 * @return string
	 */
	public function ext_addP() {
		$str = '&act=' . $GLOBALS['SOBE']->browser->act .
			'&editorNo=' . $this->browselistObj->editorNo .
			'&contentTypo3Language=' . $this->browselistObj->contentTypo3Language .
			'&contentTypo3Charset=' . $this->browselistObj->contentTypo3Charset .
			'&mode=' . $GLOBALS['SOBE']->browser->mode .
			'&expandPage=' . $GLOBALS['SOBE']->browser->expandPage .
			'&RTEtsConfigParams=' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams') .
			'&bparams=' . rawurlencode($GLOBALS['SOBE']->browser->bparams) .
			$this->addPassOnParameters;
		return $str;
	}
}
