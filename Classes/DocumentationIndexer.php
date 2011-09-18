<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Ingo Renner <ingo@typo3.org>
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
 * Indexer for typo3.org TER manuals
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	ter_doc_solrindexer
 */
class Tx_TerDocSolrindexer_DocumentationIndexer extends tx_solr_indexqueue_Indexer {

	/**
	 * Cache directory for the rendered documentation.
	 *
	 * @var	string
	 */
	protected $documentDirectory;

	/**
	 * Documentation Renderer
	 *
	 * @var	tx_terdoc_renderdocuments
	 */
	protected $documentationRenderer;

	/**
	 * Constructor
	 *
	 * @param	array	Array of indexer options
	 */
	public function __construct(array $options = array()) {
		parent::__construct($options);

		$this->documentationRenderer = tx_terdoc_renderdocuments::getInstance();
	}

	/**
	 * Indexes an item from the indexing queue.
	 *
	 * @param	tx_solr_indexqueue_Item	An index queue item
	 * @return	Apache_Solr_Response	The Apache Solr response
	 */
	public function index(tx_solr_indexqueue_Item $item) {
		$itemRecord = $item->getRecord();
		$this->documentDirectory = tx_terdoc_api::getInstance()->getDocumentDirOfExtensionVersion(
			$itemRecord['extensionkey'],
			$itemRecord['version']
		) . 'html_online/';

			// TODO clean up old documentation

		return parent::index($item);
	}

	/**
	 * Creates a single Solr Document for an item in a specific language.
	 *
	 * @param	tx_solr_indexqueue_Item	An index queue item to index.
	 * @param	integer	The language to use.
	 * @return	boolean	TRUE if item was indexed successfully, FALSE on failure
	 */
	protected function indexItem(tx_solr_indexqueue_Item $item, $language = 0) {
		$indexed   = FALSE;
		$documents = array();

		$renderedFiles = t3lib_div::getFilesInDir($this->documentDirectory, 'html');
		foreach ($renderedFiles as $fileName) {
			if ($fileName == 'index.html') {
					// skip index.html which "protects" the directory contents
				continue;
			}

			$itemRecord = $item->getRecord();
			$itemRecord['title']   = $this->getTitle($fileName);
			$itemRecord['content'] = $this->getContent($fileName);
			$itemRecord['chapter'] = $this->getChapter($fileName);
			$itemRecord['section'] = $this->getSection($fileName);
			$itemRecord['tstamp']  = $itemRecord['modificationdate'];
			$item->setRecord($itemRecord);

			$document = $this->itemToDocument($item, $language);
			$document->setField('access', 'c:0');
			$document->setField('id',     $document->id . '/' . $itemRecord['chapter'] . '/' . $itemRecord['section']);
			$document->setField('url',    $this->buildUrl($itemRecord));

				// document field processing
			$this->processDocument($item, $document);

			$documents[] = $document;
		}

		$documents = $this->preAddModifyDocuments(
			$item,
			NULL,
			$documents
		);

		$response = $this->solr->addDocuments($documents);
		if ($response->getHttpStatus() == 200) {
			$indexed = TRUE;
		}

		$this->log($item, $documents, $response);

		return $indexed;
	}


		// helper methods


	protected function getTitle($fileName) {
		$title = '';
		$file = $this->documentDirectory . $fileName;

		$content = file_get_contents($file);
		$contentDom = new SimpleXMLElement($content);

		$titleElements = $contentDom->xpath('//*[@class="title"]');
		$title = $titleElements[0]->__toString();

		return $title;
	}

	protected function getContent($fileName) {
		$file = $this->documentDirectory . $fileName;

		$contentExtractor = t3lib_div::makeInstance(
			'tx_solr_HtmlContentExtractor',
			file_get_contents($file)
		);

		return $contentExtractor->getIndexableContent();
	}

	protected function getChapter($fileName) {
		$chapter = 0;

			// cut off .html extension
		$chapterAndSection = substr($fileName, 0, -5);

		$chapter = substr($chapterAndSection, 2, 2);

		return intval($chapter);
	}

	protected function getSection($fileName) {
		$section = 1;

			// cut off .html extension
		$chapterAndSection = substr($fileName, 0, -5);

		$rawSection = substr($chapterAndSection, 5);
		if (!empty($rawSection)) {
			$section = intval($rawSection);
		}

		return $section;
	}

	protected function buildUrl(array $itemRecord) {
		$url = '';

		$contentObject = t3lib_div::makeInstance('tslib_cObj');
		$urlParameters = array(
			'extensionkey'            => $itemRecord['extensionkey'],
			'version'                 => 'current',
			'format'                  => 'ter_doc_html_onlinehtml',
			'html_readonline_chapter' => $itemRecord['chapter'],
			'html_readonline_section' => $itemRecord['section']
		);

		$url = $contentObject->typoLink_URL(array(
			'parameter'        => tx_terdoc_api::getInstance()->getViewPageIdForExtensionVersion(
				$itemRecord['extensionkey'],
				$itemRecord['version']
			),
			'additionalParams' => t3lib_div::implodeArrayForUrl('tx_terdoc_pi1', $urlParameters),
			'useCacheHash'     => TRUE
		));

		return $url;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc_solrindexer/Classes/DocumentationIndexer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc_solrindexer/Classes/DocumentationUpdateIndexer.php']);
}

?>