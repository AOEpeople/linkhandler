<?php

namespace AOE\Linkhandler\Hooks;

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
 * Class BackendUtility
 *
 * @package AOE\Linkhandler\Hooks
 * @author  Chetan Thapliyal <chetan.thapliyal@aoe.com>
 */
class BackendUtility {

	/**
	 * Restores link handler generated preview link on save-n-preview event. This link is overwritten by the workspace module.
	 *
	 * @param  integer $pageUid
	 * @param  string  $backPath
	 * @param  array   $rootLine
	 * @param  string  $anchorSection
	 * @param  string  $viewScript
	 * @param  array   $additionalGetVars
	 * @param  boolean $switchFocus
	 */
	public function preProcess($pageUid, $backPath, $rootLine, $anchorSection, &$viewScript, $additionalGetVars, $switchFocus) {
		if ($GLOBALS['BE_USER']->workspace != 0) {
			$additionalGetVars = GeneralUtility::explodeUrl2Array($additionalGetVars);

			if (isset($additionalGetVars['eID'])
				&& ($additionalGetVars['eID'] === 'linkhandlerPreview')
				&& isset($GLOBALS['_POST']['viewUrl'])) {
				$viewScript = $GLOBALS['_POST']['viewUrl'];
			}
		}
	}
} 
