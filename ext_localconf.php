<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

require_once(t3lib_extMgm::extPath('ter_doc_solrindexer') . 'classes/DocumentationUpdateMonitor.php');

$renderDocsObj = tx_terdoc_renderdocuments::getInstance();
$renderDocsObj->registerOutputFormat(
	'ter_doc_solrindexer_IndexDocument',
	'LLL:EXT:ter_doc_solrindexer/locallang.xml:format_solrIndexDocument',
	'index',
	t3lib_div::makeInstance('Tx_TerDocSolrindexer_DocumentationUpdateMonitor')
);


?>