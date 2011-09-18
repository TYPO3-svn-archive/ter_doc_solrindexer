<?php
$extensionPath = t3lib_extMgm::extPath('ter_doc_solrindexer');
return array(

	'tx_terdocsolrindexer_documentationindexer' => $extensionPath . 'Classes/DocumentationIndexer.php',
	'tx_terdocsolrindexer_documentationupdatemonitor' => $extensionPath . 'Classes/DocumentationUpdateMonitor.php',

);
?>