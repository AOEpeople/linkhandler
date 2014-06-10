<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Register linkhandler for "record"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['record'] = '&AOE\Linkhandler\Handler';

	// Register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] = 'AOE\Linkhandler\Hooks\ElementBrowser';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] = 'AOE\Linkhandler\Hooks\ElementBrowser';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][] = 'AOE\Linkhandler\Hooks\GetTable';

	// Register hook to link the "save & show" button to the single view of an record
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'AOE\Linkhandler\Hooks\TceMain';

	// Register eID for the link generation used by the "save & show" button
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['linkhandlerPreview'] = 'EXT:' . $_EXTKEY . '/Classes/Service/Eid.php';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass'][] = 'AOE\Linkhandler\Hooks\BackendUtility';
