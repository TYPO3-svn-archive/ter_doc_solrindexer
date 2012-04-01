<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2012 Ingo Renner <ingo@typo3.org>
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
 * "Record Monitor" for documentation updates.
 *
 * The class hooks into the documentation rendering process disguised as an
 * additional output format. Thus it gets executed whenever documentation is
 * rendered. It then takes the information about the manual and updates the
 * Index Queue. Later the indexer will take care of indexing the new
 * documentation.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage ter_doc_solrindexer
 */
class Tx_TerDocSolrindexer_DocumentationUpdateMonitor extends tx_terdoc_documentformat_index {

	/**
	 * Cache directory for the rendered documentation.
	 *
	 * @var string
	 */
	protected $documentDirectory;

	/**
	 * Documentation Renderer
	 *
	 * @var tx_terdoc_renderdocuments
	 */
	protected $documentationRenderer;


	/**
	 * Indexes the documentation into Apache Solr using the files rendered by
	 * EXT:ter_doc_html before.
	 *
	 * @param string $documentDir: Absolute directory for the document currently being processed.
	 * @return void
	 */
	public function renderCache($documentDir) {
		$this->documentDirectory     = $documentDir;
		$this->documentationRenderer = tx_terdoc_renderdocuments::getInstance();

		$extensionManualMetaData = $this->getExtensionManualMetaData();

		$this->removeOldDocumentationIndexQueueItems($extensionManualMetaData);

		$latestManualVersion = array_pop($extensionManualMetaData);
		$indexQueue = t3lib_div::makeInstance('tx_solr_indexqueue_Queue');
		$indexQueue->updateItem('tx_terdoc_manuals', $latestManualVersion['uid']);
	}

	/**
	 * Usually would return TRUE if a rendered document for the given extension
	 * version is available. Since we only want to index but not display
	 * documentation we always return FALSE.
	 *
	 * @param string $extensionKey: Extension key of the document
	 * @param string $version: Version number of the document
	 * @return boolean TRUE if rendered version is available, otherwise FALSE
	 */
	public function isAvailable($extensionKey, $version) {
		return FALSE;
	}

	/**
	 * Gets the full extension manual meta data records as created by the
	 * documentation renderer.
	 *
	 * @return array Manual meta data record
	 */
	protected function getExtensionManualMetaData() {
		$explodedPath = t3lib_div::trimExplode('/', $this->documentDirectory, TRUE);
		$extensionKeyAndVersion = array_pop($explodedPath);
		list($extensionKey, $extensionVersion) = explode('-', $extensionKeyAndVersion);

		$extensionManualMetaData = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_terdoc_manuals',
			'extensionkey = "' . $extensionKey . '"'
				. ' AND pid = "' . intval($this->documentationRenderer->getStoragePid()) . '"',
			'',
			'uid ASC'
		);

		return $extensionManualMetaData;
	}

	/**
	 * Removes all possible Index Queue items for the current extension's
	 * manual.
	 *
	 * @param array $extensionManuals
	 */
	protected function removeOldDocumentationIndexQueueItems(array $extensionManuals) {

		$extensionManualIds = array();
		foreach ($extensionManuals as $extensionManual) {
			$extensionManualIds[] = $extensionManual['uid'];
		}

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_solr_indexqueue_item',
			'item_type = "tx_terdoc_manuals"'
				. ' AND item_uid IN(' .implode(',', $extensionManualIds) . ')'
		);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc_solrindexer/Classes/DocumentationUpdateMonitor.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc_solrindexer/Classes/DocumentationUpdateMonitor.php']);
}

?>