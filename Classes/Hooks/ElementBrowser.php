<?php
namespace AOE\Linkhandler\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel P�tzinger <daniel.poetzinger@aoemedia.de>
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
 * hook to adjust link wizard (linkbrowser)
 *
 * @author Daniel Poetzinger (AOE media GmbH)
 * @package Linkhandler
 */
class ElementBrowser implements \TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface {

	/**
	 * the browse_links object
	 */
	protected $pObj;

	protected $allAvailableTabHandlers = array();

	/**
	 * TCA configuration of "blindLinkOptions" for the current field
	 *
	 * @var string OPTIONAL Comma separated list
	 */
	protected $blindLinkOptions = '';

	/**
	 * initializes the hook object
	 *
	 * @param \TYPO3\CMS\Recordlist\Browser\ElementBrowser $pObj
	 * @param array $params
	 * @return void
	 */
	public function init($pObj, $params) {
		$this->pObj = $pObj;

		if ((is_array($this->pObj->P['params'])) && (array_key_exists('blindLinkOptions', $this->pObj->P['params']))) {
			$this->blindLinkOptions = $this->pObj->P['params']['blindLinkOptions'];
		}

		$this->_checkConfigAndGetDefault();
		$tabs = $this->getTabsConfig();
		foreach ($tabs as $key => $tabConfig) {
			if ($this->isRTE()) {
				/** @todo this line had a comment "for 4.3". is it still required with 6.x? */
				$this->pObj->anchorTypes[] = $key;
			}
		}
		$this->allAvailableTabHandlers = $this->getAllRegisteredTabHandlerClassnames();
	}

	/**
	 * modifies the menu definition and returns it
	 *
	 * @param array $menuDef menu definition
	 * @return array modified menu definition
	 */
	public function modifyMenuDefinition($menuDef) {
		$tabs = $this->getTabsConfig();
		foreach ($tabs as $key => $tabConfig) {
			$menuDef[$key]['isActive'] = $this->pObj->act == $key;
			$menuDef[$key]['label'] = $tabConfig['label'];
			$menuDef[$key]['url'] = '#';
			$addPassOnParams = $this->getaddPassOnParams();
			$addPassOnParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($addPassOnParams), '', TRUE);
			$menuDef[$key]['addParams'] = 'onclick="jumpToUrl(\'?act=' . $key .
				'&editorNo=' . $this->pObj->editorNo .
				'&contentTypo3Language=' . $this->pObj->contentTypo3Language .
				'&contentTypo3Charset=' . $this->pObj->contentTypo3Charset .
				$addPassOnParams .
				'\');return false;"';
		}

		return $menuDef;
	}

	/**
	 * returns a new tab for the browse links wizard
	 *
	 * @param string current link selector action
	 * @return string a tab for the selected link action
	 */
	public function getTab($act) {
		if (!$this->_isOneOfLinkhandlerTabs($act))
		    return FALSE;

		if ($this->isRTE()) {
			if (isset($this->pObj->classesAnchorJSOptions)) {
				// works for 4.1.x patch, in 4.2 they make this property protected! -> to enable classselector in 4.2 easoiest is to path rte.
				$this->pObj->classesAnchorJSOptions[$act] = @$this->pObj->classesAnchorJSOptions['page'];
			}
		}

		$configuration = $this->getTabConfig($act);
		// get current href value (diffrent for RTE and normal browselinks)
		if ($this->isRTE()) {
			$currentValue = $this->pObj->curUrlInfo['value'];
		} else {
			$currentValue = $this->pObj->P['currentValue'];
		}

		// get the tabHandler
		$tabHandlerClass = 'AOE\Linkhandler\RecordTab';
		if (class_exists($configuration['tabHandler'])) {
			$tabHandlerClass = $configuration['tabHandler'];
		}

		$tabHandler = new $tabHandlerClass($this->pObj,$this->getaddPassOnParams,$configuration,$currentValue,$this->isRTE(), $this->getCurrentPageId());
		$content = $tabHandler->getTabContent();

		return $content;
	}

	/**
	 * adds new items to the currently allowed ones and returns them
	 *
	 * @param array $allowedItems currently allowed items
	 * @return array currently allowed items plus added items
	 */
	public function addAllowedItems($allowedItems) {
		if (is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
				if (is_array($tabConfig)) {
					$key = substr($name, 0, -1);
					$allowedItems[] = $key;
				}
			}
		}
		return $allowedItems;
	}


	/**
	 * checks the current URL and returns a info array. This is used to
	 * tell the link browser which is the current tab based on the current URL.
	 * function should at least return the $info array.
	 *
	 * @param string $href
	 * @param string $siteUrl
	 * @param array $info Current info array.
	 * @return array $info a infoarray for browser to tell them what is current active tab
	 */
	public function parseCurrentUrl($href, $siteUrl, $info) {

			// depending on link and setup the href string can contain complete absolute link
			if (substr($href, 0, 7) == 'http://') {
				if ($_href = strstr($href, '?id=')) {
					$href = substr($_href, 4);
				} else {
					$href = substr(strrchr($href, '/'), 1);
				}
			}

			//ask the registered tabHandlers:
			foreach ($this->allAvailableTabHandlers as $handler) {
				$result = call_user_func(array($handler, 'getLinkBrowserInfoArray'), $href, $this->getTabsConfig());
				if (count($result) > 0 && is_array($result)) {
					return array_merge($info, $result);
				}
			}
			return $info;
	}

	/**
	* returns a array of names available tx_linkhandler_tabHandler
	*/
	protected function getAllRegisteredTabHandlerClassnames() {
		$default = array('AOE\Linkhandler\RecordTab');

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'] as $tabHandler) {
				list($file, $class) = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode(':', $tabHandler, 2);
				$default[] = $class;
			}
		}
		return $default;
	}


	/**
	 * Return the ID of current page.
	 *
	 * @return integer
	 */
	private function getCurrentPageId() {
		$pageId = 0;

		if ($this->isRTE()) {
			$confParts = explode(':', $this->pObj->RTEtsConfigParams);
			$pageId = $confParts[5];
		} else {
			$P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');

			if (is_array($P) && array_key_exists('pid', $P)) {
				$pageId = $P['pid'];
			} else {
				$pageId = $this->findPageIdFromData($P);
			}
		}

		return $pageId;
	}


	/**
	 * Try to find the current page id from the value containing the itemNode value.
	 *
	 * @param array $params $_GET Parameter from linkwizard
	 * @return integer
	 */
	private function findPageIdFromData($params) {
		$pageId = 0;

		if (is_array($params) && array_key_exists('itemName', $params)) {

			preg_match('~data\[([^]]*)\]\[([^]]*)\]~', $params['itemName'], $matches);
			$recordArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($matches['1'], $matches['2']);

			if (is_array($recordArray)) {
				$pageId = $recordArray['pid'];
			}
		}

		return $pageId;
	}

	/**
	 * checks if
	 * $this->pObj->thisConfig['tx_linkhandler.'] is set, and if not it trys to load default from
	 * TSConfig key mod.tx_linkhandler.
	 * (in case the hook is called from a RTE, this configuration might exist because it is configured in RTE.defaul.tx_linkhandler)
	 * In mode RTE: the parameter RTEtsConfigParams have to exist
	 * In mode WIzard: the parameter P[pid] have to exist
	 *
	 * @return void
	*/
	private function _checkConfigAndGetDefault() {

		if ($this->pObj->mode == 'rte') {
			$RTEtsConfigParts = explode(':', $this->pObj->RTEtsConfigParams);
			$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig('RTE',\TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->pObj->thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
		} elseif (! is_array($this->pObj->thisConfig['tx_linkhandler.']) ) {
			$pid = $this->getCurrentPageId();
			$modTSconfig = $GLOBALS["BE_USER"]->getTSConfig("mod.tx_linkhandler", \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($pid));

			$this->pObj->thisConfig['tx_linkhandler.'] = $modTSconfig['properties'];
		}
	}

	/**
	* returns the complete configuration (tsconfig) of all tabs
	*/
	private function getTabsConfig() {
		$tabs = array();

		if (is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
				if (is_array($tabConfig)) {
					$key = substr($name, 0, -1);

						/**
						 * @internal if we found the current key within the blindLinkOptions in
						 * the TCA field configuration then skip and do not append this item to the struct
						 */
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->blindLinkOptions, $key)) {
						continue;
					}

					$tabs[$key] = $tabConfig;
				}
			}
		}
		return $tabs;
	}

	/**
	* returns config for a single tab
	*/
	private function getTabConfig($tabKey) {
		$conf = $this->getTabsConfig();
		return $conf[$tabKey];
	}

	/**
	 * returns additional addonparamaters - required to keep several informations for the RTE linkwizard
	 */
	protected function getaddPassOnParams() {
		$urlParams = '';
		if (!$this->isRTE()) {
			$P2 = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
			if (is_array($P2) && !empty($P2) ) {
				$urlParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('P', $P2);
			}
		}
		return $urlParams;
	}

	/**
	* returns if the current linkwizard is RTE or not
	**/
	protected function isRTE() {
		return($this->pObj->mode == 'rte');
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	private function _isOneOfLinkhandlerTabs ($key) {
		foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
			if (is_array($tabConfig)) {
				$akey = substr($name, 0, -1);
				if ($akey == $key) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}
}
