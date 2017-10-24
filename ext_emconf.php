<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'qbevents_kesearch',
    'description' => 'ke_search indexer for TYPO3 Event Management',
    'category' => '',
    'author' => 'Benjamin Franzke',
    'author_email' => 'bfr@qbus.de',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.1',
    'constraints' => array(
        'depends' => array(
            'typo3' => '6.2.0-7.6.99',
            'qbevents' => '0.8.0',
            'ke_search' => '2.4.0',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
    'autoload' => array(
        'psr-4' => array(
            'Qbus\\QbeventsKesearch\\' => 'Classes',
        ),
    ),
);
