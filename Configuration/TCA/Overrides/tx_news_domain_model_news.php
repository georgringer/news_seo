<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tx_news_domain_model_news',
    [
        'robots_index' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.robots_index',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                    ],
                ],
            ],
        ],
        'robots_follow' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.robots_follow',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
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
                'default' => 1,
            ],
        ],
        'canonical_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:news_seo/Resources/Private/Language/locallang.xlf:tx_news_domain_model_news.canonical_link',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'size' => 30,
                'eval' => 'trim',
                'softref' => 'typolink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'mail,folder,telephone',
                            'blindLinkFields' => 'class, target, title',
                        ],
                    ],
                ],
            ]
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
