<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'qbevents_kesearch',
    'description' => 'ke_search indexer for TYPO3 Event Management',
    'category' => '',
    'author' => 'Benjamin Franzke',
    'author_email' => 'bfr@qbus.de',
    'state' => 'stable',
    'version' => '1.1.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '10.4.0-10.4.99',
            'qbevents' => '0.11.0',
            'ke_search' => '3.0.0',
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
