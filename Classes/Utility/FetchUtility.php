<?php
declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Utility;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FetchUtility
{

    public static function isNoIndex(int $newsId): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_news_domain_model_news');
        $row = $queryBuilder->select('uid', 'no_index')
            ->from('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();

        return (bool)$row['no_index'];
    }

}