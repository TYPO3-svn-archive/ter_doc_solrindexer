<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Ingo Renner <ingo@typo3.org>
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
 * Initializes extension documentation items in Index Queue.
 *
 * This is used only when initializing the Index Queue through the EXT:solr
 * backend module. The initializer gets all the latest versions of each
 * extension's manual and add the accordant item to the Index Queue. Later the
 * indexer then takes care of actually indexing the documentation.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage ter_doc_solrindexer
 */
class Tx_TerDocSolrindexer_DocumentationIndexQueueInitializer extends tx_solr_indexqueue_initializer_Abstract {

	/**
	 * Overrides the general setType() implementation, forcing type
	 * to "tx_terdoc_manuals".
	 *
	 * @param string $type Type to initialize (ignored).
	 * @see tx_solr_IndexQueueInitializer::setType()
	 */
	public function setType($type) {
		$this->type = 'tx_terdoc_manuals';
	}

	/**
	 * Initializes tx_terdoc_manuals Index Queue items for a certain site.
	 *
	 * @return boolean TRUE if initialization was successful, FALSE on error.
	 * @see tx_solr_IndexQueueInitializer::initialize()
	 * @see tx_solr_indexqueue_initializer_Abstract::initialize()
	 */
	public function initialize() {
		$initialized = FALSE;

		$latestManuals = $this->getLatestManuals();

		$latestManualUids = array();
		foreach ($latestManuals as $manual) {
			$latestManualUids[] = $manual['uid'];
		}

		$initializationQuery = 'INSERT INTO tx_solr_indexqueue_item (root, item_type, item_uid, indexing_configuration, changed) '
			. $this->buildSelectStatement() . ' '
			. 'FROM ' . $this->type . ' '
			. 'WHERE uid IN(' . implode(',', $latestManualUids) . ')';


		$GLOBALS['TYPO3_DB']->sql_query($initializationQuery);

		if (!$GLOBALS['TYPO3_DB']->sql_error()) {
			$initialized = TRUE;
		}

		$this->logInitialization($initializationQuery);

		return $initialized;
	}

	/**
	 * Builds the SELECT part of the Index Queue initialization query.
	 *
	 * @return string The SQL SELECT part of the initialization query.
	 */
	protected function buildSelectStatement() {
		$select = 'SELECT '
			. '\'' . $this->site->getRootPageId() . '\' as root, '
			. '\'' . $this->type . '\' AS item_type, '
			. 'uid, '
			. '\'' . $this->indexingConfigurationName . '\' as indexing_configuration, '
			. 'modificationdate';

		return $select;
	}

	/**
	 * Gets the uid of the latest version for every extension.
	 *
	 * @return array Array of uids and extensionkeys
	 */
	protected function getLatestManuals() {
		$latestManuals = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'MAX(uid) AS uid, extensionkey',
			$this->type,
			'',
			'extensionkey'
		);

		return $latestManuals;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc_solrindexer/Classes/DocumentationIndexQueueInitializer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc_solrindexer/Classes/DocumentationIndexQueueInitializer.php']);
}

?>