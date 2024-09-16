<?php
declare(strict_types=1);

namespace GeorgRinger\NewsSeo\EventListener;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use GeorgRinger\News\Event\NewsDetailActionEvent;
use GeorgRinger\NewsSeo\Domain\Model\News;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NewsDetailActionEventListener
{

    public function __invoke(NewsDetailActionEvent $event): void
    {
        /** @var News $news */
        $news = $event->getAssignedValues()['newsItem'];
        if (is_a($news, News::class) && $news) {
            $robots = [
                $news->isRobotsIndex() ? 'index' : 'noindex',
                $news->isRobotsFollow() ? 'follow' : 'nofollow',
                $news->getMaxImagePreviewString(),
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
}
