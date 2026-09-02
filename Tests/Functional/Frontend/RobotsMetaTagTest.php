<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\Frontend;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class RobotsMetaTagTest extends AbstractFrontendTestCase
{
    #[Test]
    #[DataProvider('articleProvider')]
    public function robotsMetaTagIsRenderedFromTheArticle(int $newsId, string $expected): void
    {
        $html = $this->renderDetail($newsId);

        self::assertSame([$expected], $this->extractMetaTag($html, 'robots'));
    }

    public static function articleProvider(): array
    {
        return [
            'indexed and followed' => [1, 'index,follow'],
            'excluded from the index' => [2, 'noindex,nofollow'],
            'large image preview' => [3, 'index,follow,max-image-preview:large'],
            'indexed, not followed, standard preview' => [4, 'index,nofollow,max-image-preview:standard'],
        ];
    }

    /**
     * The point of the extension: the article decides, not the page. Page 4
     * carries no_index and no_follow, from which the Core would otherwise
     * render "noindex,nofollow".
     */
    #[Test]
    public function articleOverrulesTheRobotsSettingsOfThePage(): void
    {
        $html = $this->renderDetail(1, self::NOINDEX_DETAIL_PAGE_ID);

        self::assertSame(['index,follow'], $this->extractMetaTag($html, 'robots'));
    }

    /**
     * The inverse: an article that says noindex has to win on a page that is
     * perfectly indexable.
     */
    #[Test]
    public function articleCanExcludeItselfOnAnIndexablePage(): void
    {
        $html = $this->renderDetail(2);

        self::assertSame(['noindex,nofollow'], $this->extractMetaTag($html, 'robots'));
    }
}
