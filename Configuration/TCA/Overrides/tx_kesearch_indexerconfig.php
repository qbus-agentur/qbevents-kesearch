<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/* Note: we can not add our indexer to the tx_kesearch_indexerconfig type select list here.
   \tx_kesearch_indexer assumes those static entries are ke_search's native indexers and tries to load them from their sources. */
$GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['sysfolder']['displayCond'] .= ',qbevents';
$GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['index_use_page_tags']['displayCond'] .= ',qbevents';
