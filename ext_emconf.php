<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Additional SEO features for EXT:news',
    'description' => 'Individual indexing/robot information for each news article record',
    'category' => 'frontend',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '2.2.2',
    'constraints' =>
        [
            'depends' => [
                'typo3' => '12.4.0-13.4.99',
                'seo' => '12.4.0-13.4.99',
                'news' => '10.0.0-12.99.99',
            ],
            'conflicts' => [],
            'suggests' => [],
        ],
];
