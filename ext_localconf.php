<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerIndexerConfiguration'][] =
    Qbus\QbeventsKesearch\Indexer\Types\QbeventsIndexer::class;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customIndexer'][] =
    Qbus\QbeventsKesearch\Indexer\Types\QbeventsIndexer::class;
