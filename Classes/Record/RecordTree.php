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
 * Class which generates the page tree for records, specific version for linkhandler extension
 *  -> shows records on the selected page and makes them clickable to get the link
 *
 * @author Daniel Poetzinger (AOE media GmbH)
 * @package Linkhandler
 */
class RecordTree extends \TYPO3\CMS\Backend\Tree\View\BrowseTreeView {

	/**
	 * @var \TYPO3\CMS\Rtehtmlarea\BrowseLinks
	 */
	public $browselistObj = NULL;

	/**
	 * returns the uids of the childs of page
	 *
	 * @param integer $pageId
	 * @return array
	 */
	protected function getRootLineChildPids($pageId) {
		$childPages = array();
		$pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');
		$pageRepository->init(TRUE);
		$rootLine = $pageRepository->getMenu($pageId);
		foreach ($rootLine as $v) {
			$childPages[] = $v['uid'];
		}
		return $childPages;
	}

	/**
	 * Create the page navigation tree in HTML
	 *
	 * @param array $treeArray Tree array
	 * @return	string HTML output.
	 */
	public function printTree(array $treeArray) {
		$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
		if (!is_array($treeArray)) {
			$treeArray = $this->tree;
		}

		$out = '';
		$c = 0;
		$dofiltering = FALSE; //should the pagetree be filter to show only $onlyPids
		$onlyPids = array();

		if (isset($this->browselistObj->thisConfig['tx_linkhandler.'][$this->browselistObj->act . '.']['onlyPids'])) {
			$onlyPids = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->browselistObj->thisConfig['tx_linkhandler.'][$this->browselistObj->act . '.']['onlyPids']);
			if ($this->browselistObj->thisConfig['tx_linkhandler.'][$this->browselistObj->act . '.']['onlyPids.']['recursive'] == 1) {
				// merge childs
				foreach ($onlyPids as $actualPid) {
					$onlyPids = array_merge($onlyPids, $this->getRootLineChildPids($actualPid));
				}
			}
			$dofiltering = TRUE;
		}

		foreach ($treeArray as $k => $v) {
			if ($dofiltering && (!in_array($v['row']['uid'], $onlyPids))) {
				continue;
			}

			$c++;
			$bgColorClass = ($c + 1) % 2 ? 'bgColor' : 'bgColor-10';
			if ($GLOBALS['SOBE']->browser->curUrlInfo['act'] == 'page' &&
				$GLOBALS['SOBE']->browser->curUrlInfo['pageid'] == $v['row']['uid'] &&
				$GLOBALS['SOBE']->browser->curUrlInfo['pageid']
			) {
				$arrCol = '<td><img' .
					\TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_right.gif', 'width="5" height="9"') .
					' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass = 'bgColor4';
			} else {
				$arrCol = '<td></td>';
			}
			$addPassOnParams = $this->getaddPassOnParams();

            if($this->browselistObj->thisConfig['tx_linkhandler.'][$this->browselistObj->act.'.']['listTables'] != 'pages') {
                $aOnClick = 'return jumpToUrl(\'' . $this->thisScript . '?act=' .
                $GLOBALS['SOBE']->browser->act .
                '&editorNo=' . $GLOBALS['SOBE']->browser->editorNo .
                '&contentTypo3Language=' . $GLOBALS['SOBE']->browser->contentTypo3Language .
                '&contentTypo3Charset=' . $GLOBALS['SOBE']->browser->contentTypo3Charset .
                '&mode=' . $GLOBALS['SOBE']->browser->mode .
                '&expandPage=' . $v['row']['uid'] .
                $addPassOnParams . '\');';
            } else {
                $aOnClick = 'return link_spec(\'record:pages:'.$v['row']['uid'].'\');';
            }

			$cEbullet = !$this->ext_isLinkable($v['row']['doktype'], $v['row']['uid']) ?
						'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '"><img' .
						\TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/ol/arrowbullet.gif', 'width="18" height="16"') .
						' alt="" /></a>' :
						'';
			$out .= '
				<tr class="' . $bgColorClass . '">
					<td nowrap="nowrap"' . ($v['row']['_CSSCLASS'] ? ' class="' . $v['row']['_CSSCLASS'] . '"' : '') . '>' .
					$v['HTML'] .
					'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $this->getTitleStr($v['row'], $titleLen) . '</a>' .
					'</td>' .
					$arrCol .
					'<td>' . $cEbullet . '</td>
				</tr>';
		}

		$out = '
			<!--
				Navigation Page Tree:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-tree">
				' . $out . '
			</table>';
		return $out;
	}

	/**
	 * @return mixed
	 */
	protected function getaddPassOnParams() {
		if ($this->pObj->mode == 'rte') {
			return;
		}
		if (empty($this->cachedParams)) {
			$pGet = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
			$parameters = array();
			if (is_array($pGet)) {
				foreach ($pGet as $k => $v) {
					if (!is_array($v) && $k != 'returnUrl' && $k != 'md5ID' && $v != '') {
						$parameters[$k] = $v;
					}
				}
			}
			$this->cachedParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('P', $parameters);
		}
		return $this->cachedParams;
	}


	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param string $bMark If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return string Link-wrapped input string
	 */
	public function PM_ATagWrap($icon, $cmd, $bMark = '') {
		if ($bMark) {
			$anchor = '#' . $bMark;
			$name = ' name="' . $bMark . '"';
		}
		$aOnClick = "return jumpToUrl('" . $this->thisScript . '?PM=' . $cmd . $this->getaddPassOnParams() . "','" . $anchor . "');";

		return '<a href="#"' . $name . ' onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
	}

	/**
	 * Returns TRUE if a doktype can be linked.
	 *
	 * @param integer $doktype Doktype value to test
	 * @param integer $uid uid to test.
	 * @return boolean
	 */
	protected function ext_isLinkable($doktype, $uid) {
		if ($uid && $doktype < 199) {
			return TRUE;
		}
	}
}
