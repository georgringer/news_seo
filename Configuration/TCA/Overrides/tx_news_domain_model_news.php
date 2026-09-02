<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tx_news_domain_model_news',
    [
        'robots_index' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.robots_index',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'robots_follow' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.robots_follow',
            'config' => [
                'type' => 'check',
                'default' => 1,
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
                        'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.max_image_preview.none',
                        'value' => 0,
                    ],
                    [
                        'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.max_image_preview.standard',
                        'value' => 1,
                    ],
                    [
                        'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.max_image_preview.large',
                        'value' => 2,
                    ],
                ],
                'default' => 1,
            ],
        ],
        'canonical_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.canonical_link',
            'config' => [
                'type' => 'link',
                'size' => 30,
                // allowed types tx_news: linkhandler must be set (typically called "tx_news" TCEMAIN.linkHandler.tx_news)
                'allowedTypes' => ['page', 'url', 'tx_news'],
                'appearance' => [
                    'browserTitle' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.canonical_link',
                    'allowedOptions' => ['params'],
                ],
            ],
        ],
    ]
);

$GLOBALS['TCA']['tx_news_domain_model_news']['palettes']['newsseoindex'] = [
    'label' => 'LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.robots',
    'showitem' => 'robots_index,robots_follow,max_image_preview',
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_news_domain_model_news',
    '--palette--;;newsseoindex,canonical_link',
    '',
    'after:sitemap_priority'
);
