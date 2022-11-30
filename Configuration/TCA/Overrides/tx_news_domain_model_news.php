<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tx_news_domain_model_news',
    [
        'no_index' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.no_index',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'no_follow' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.no_follow',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'max_image_preview' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.max_image_preview',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.max_image_preview.none',
                        0,
                    ],
                    [
                        'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.max_image_preview.standard',
                        1,
                    ],
                    [
                        'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.max_image_preview.large',
                        2,
                    ],
                ],
                'default' => 0,
            ],
        ],
    ]
);

$GLOBALS['TCA']['tx_news_domain_model_news']['palettes']['newsseoindex'] = [
    'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.robots',
    'showitem' => 'no_index,no_follow,max_image_preview',
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_news_domain_model_news',
    '--palette--;;newsseoindex,',
    '',
    'after:sitemap_priority'
);
