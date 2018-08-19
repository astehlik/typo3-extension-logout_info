<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Logout info',
    'description' => 'Writes the reason of Backend logouts to the Backend log.',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Alexander Stehlik',
    'author_email' => 'alex.deleteme@stehlik-online.de',
    'author_company' => '',
    'version' => '9.0.0',
    'constraints' => [
        'depends' => ['typo3' => '9.0.0-9.99.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
];
