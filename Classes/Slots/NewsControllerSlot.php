<?php
declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Slots;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use GeorgRinger\NewsSeo\Domain\Model\News;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Support of outdated signal slot
 * @deprecated
 */
class NewsControllerSlot
{

    public function detailActionSlot($news = null): void
    {
        if (!$news) {
            return;
        }
        /** @var News $news */

        $robots = [
            $news->isRobotsIndex() ? 'index' : 'noindex',
            $news->isRobotsFollow() ? 'follow' : 'nofollow',
        ];
        if (!$news->isRobotsIndex()) {
            $robots[] = $news->getMaxImagePreviewString();
        }

        $robots = array_filter($robots);
        $metaTagManagerRegistry = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);

        $manager = $metaTagManagerRegistry->getManagerForProperty('robots');
        $manager->addProperty('robots', implode(',', $robots));
    }
}
