<?php
namespace AOE\Linkhandler\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2016, AOE GmbH <dev@aoe.com>
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
 * Class GetTable
 *
 * @package AOE\Linkhandler\Hooks
 */
class GetTable implements \TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface
{

    /**
     * Modifies the DB list query
     *
     * Default behavior is that the db list only shows the localisation parent records. If a user have set the
     * language settings out of the page module, so the user get the specific language of records listed.
     *
     * @param string $table the current database table
     * @param integer $pageId the record's page ID
     * @param string $additionalWhereClause an additional WHERE clause
     * @param string $selectedFieldsList comma separated list of selected fields
     * @param \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $parentObject parent localRecordList object
     *
     * @return void
     */
    public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject)
    {

        if ((bool)$parentObject->localizationView === false) {
            $mode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mode');

            if ($mode == 'wizard' || $mode == 'rte') {
                if (
                    is_array($GLOBALS['TCA'][$table]['ctrl'])
                    && array_key_exists('transOrigPointerField', $GLOBALS['TCA'][$table]['ctrl'])
                    && array_key_exists('languageField', $GLOBALS['TCA'][$table]['ctrl'])
                ) {
                    $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
                    $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
                    $sysLanguageId = $this->getUserSysLanguageUidForLanguageListing();

                    // Even if no language has been selected in the TemplaVoilà page module, this might still be a localized record
                    if (0 === (int)$sysLanguageId) {
                        $sysLanguageId = $this->getSysLanguageUidFromParentRecord($parentObject);
                    }

                    // if the page module is configured to display a different language than default
                    if ($sysLanguageId > 0) {
                        $additionalWhereClause .= 'AND ' . $languageField . ' = ' . $sysLanguageId;
                    } else {
                        // show only the localisation parent records for selection
                        $additionalWhereClause .= 'AND (' . $languageField . ' <= 0 || ' . $transOrigPointerField . ' = 0)';
                    }
                }
            }
        }
    }

    /**
     * Find the selected sys_language_uid which are set by the templavoila page module.
     *
     * @return integer
     */
    private function getUserSysLanguageUidForLanguageListing()
    {
        $sysLanguageId = 0;
        $moduleKey = 'web_txtemplavoilaM1';

        if (array_key_exists('web_tvpagemodulM1', $GLOBALS['BE_USER']->uc['moduleData'])) {
            $moduleKey = 'web_tvpagemodulM1';
        }

        if (
            is_array($GLOBALS['BE_USER']->uc['moduleData'][$moduleKey]) &&
            array_key_exists('language', $GLOBALS['BE_USER']->uc['moduleData'][$moduleKey]) &&
            (\TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger($GLOBALS['BE_USER']->uc['moduleData'][$moduleKey]['language']) > 0)
        ) {
            $sysLanguageId = $GLOBALS['BE_USER']->uc['moduleData'][$moduleKey]['language'];
        }

        return $sysLanguageId;
    }

    /**
     * Get sys_language_uid of the parent (tt_content) record
     *
     * @param \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $databaseRecordList
     *
     * @return integer
     */
    protected function getSysLanguageUidFromParentRecord(
        \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $databaseRecordList
    ) {
        $sysLanguageId = 0;

        if (
            property_exists($databaseRecordList, 'browselistObj')
            && property_exists($databaseRecordList->browselistObj, 'P')
            && is_array($databaseRecordList->browselistObj->P)
            && array_key_exists('table', $databaseRecordList->browselistObj->P)
            && array_key_exists('uid', $databaseRecordList->browselistObj->P)
        ) {
            $parentRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord(
                $databaseRecordList->browselistObj->P['table'],
                $databaseRecordList->browselistObj->P['uid']
            );
            if (
                is_array($parentRecord)
                && array_key_exists('sys_language_uid', $parentRecord)
                && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($parentRecord['sys_language_uid'])
            ) {
                $sysLanguageId = (int)$parentRecord['sys_language_uid'];
            }
        }

        return $sysLanguageId;
    }
}
