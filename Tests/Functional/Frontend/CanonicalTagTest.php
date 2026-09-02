<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\Frontend;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use PHPUnit\Framework\Attributes\Test;

class CanonicalTagTest extends AbstractFrontendTestCase
{
    /**
     * An article without a canonical_link keeps whatever EXT:seo produced for
     * the page - the listener has to stay out of the way.
     */
    #[Test]
    public function ordinaryArticleKeepsTheCanonicalOfThePage(): void
    {
        $html = $this->renderDetail(1);

        self::assertSame(['http://localhost/detail'], $this->extractLinkTag($html, 'canonical'));
    }

    #[Test]
    public function canonicalLinkOfTheArticleWins(): void
    {
        $html = $this->renderDetail(5);

        self::assertSame(['http://localhost/list'], $this->extractLinkTag($html, 'canonical'));
    }

    /**
     * The feature from the README: EXT:seo refuses to emit a canonical on a
     * page carrying no_index, but an article that wants to be indexed still
     * needs one.
     */
    #[Test]
    public function canonicalIsEmittedForAnIndexableArticleOnANoIndexPage(): void
    {
        $html = $this->renderDetail(1, self::NOINDEX_DETAIL_PAGE_ID);

        self::assertSame(['http://localhost/detail-noindex'], $this->extractLinkTag($html, 'canonical'));
    }

    /**
     * ... and only then. If neither the page nor the article wants to be
     * indexed, there is nothing to canonicalize.
     */
    #[Test]
    public function noCanonicalWhenNeitherPageNorArticleIsIndexable(): void
    {
        $html = $this->renderDetail(2, self::NOINDEX_DETAIL_PAGE_ID);

        self::assertSame([], $this->extractLinkTag($html, 'canonical'));
    }

    /**
     * An article excluded from the index on an indexable page: EXT:seo has
     * already emitted the page canonical and the listener returns early, so
     * the tag stays. Pinned because the early return in the listener reads
     * like it might drop it.
     */
    #[Test]
    public function noIndexArticleOnAnIndexablePageKeepsThePageCanonical(): void
    {
        $html = $this->renderDetail(2);

        self::assertSame(['http://localhost/detail'], $this->extractLinkTag($html, 'canonical'));
    }
}
