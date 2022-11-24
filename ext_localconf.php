<?php

$GLOBALS['TYPO3_CONF_VARS']['EXT']['news']['classes']['Domain/Model/News'][] = 'news_seo';

if (!class_exists(\GeorgRinger\News\Event\NewsDetailActionEvent::class)) {
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
    $signalSlotDispatcher->connect(
        'GeorgRinger\\News\\Controller\\NewsController',
        'detailAction',
        'GeorgRinger\\NewsSeo\\Slots\\NewsControllerSlot',
        'detailActionSlot'
    );
}