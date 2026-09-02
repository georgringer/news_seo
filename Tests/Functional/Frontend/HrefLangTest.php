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
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Three listeners write the hreflang set, in this order:
 *
 *   1. typo3-seo/hreflangGenerator   - builds it from the translated pages
 *   2. ext-news/modify-hreflang      - drops languages the article does not
 *                                      exist in, for "strict" site languages
 *   3. ext-seonews/modify-hreflang   - this extension, which rebuilds the set
 *                                      from scratch
 *
 * Only the third one knows about robots_index and about a canonical_link on
 * the translated page record; everything else it does duplicates step 2. The
 * tests therefore go through the rendered page, which is the only place where
 * the interplay of all three is visible.
 *
 * Note that the Core renders the tags only when more than one hreflang
 * survives (RequestHandler::generateHrefLangTags), so "one language left" and
 * "no languages left" both come out as an empty set.
 */
class HrefLangTest extends AbstractFrontendTestCase
{
    #[Test]
    public function everyTranslationOfTheArticleGetsAnHrefLang(): void
    {
        $html = $this->renderDetail(10);

        self::assertSame(
            [
                'en-US' => 'http://localhost/detail',
                'de-DE' => 'http://localhost/de/detail',
                'fr-FR' => 'http://localhost/fr/detail',
                'x-default' => 'http://localhost/detail',
            ],
            $this->extractHrefLangTags($html)
        );
    }

    /**
     * The page exists in French, the article does not, and the French site
     * language is "strict" - so pointing at /fr/detail would advertise a page
     * that cannot show this article.
     */
    #[Test]
    public function languageWithoutATranslationOfTheArticleIsDropped(): void
    {
        $html = $this->renderDetail(11);

        self::assertSame(
            [
                'en-US' => 'http://localhost/detail',
                'de-DE' => 'http://localhost/de/detail',
                'x-default' => 'http://localhost/detail',
            ],
            $this->extractHrefLangTags($html)
        );
    }

    /**
     * The feature EXT:news alone does not have: an article excluded from the
     * index must not be advertised in any language, even though the page it
     * sits on is perfectly indexable.
     */
    #[Test]
    public function articleExcludedFromTheIndexGetsNoHrefLangAtAll(): void
    {
        $html = $this->renderDetail(12);

        self::assertSame([], $this->extractHrefLangTags($html));
    }

    /**
     * The other one: a translated page carrying its own canonical_link is not
     * a valid alternate, so that language drops out. Page 5 has one on its
     * French translation only.
     */
    #[Test]
    public function languageWhosePageHasACanonicalIsDropped(): void
    {
        $html = $this->renderDetail(10, self::CANONICAL_DETAIL_PAGE_ID);

        self::assertSame(
            [
                'en-US' => 'http://localhost/detail-canonical',
                'de-DE' => 'http://localhost/de/detail-canonical',
                'x-default' => 'http://localhost/detail-canonical',
            ],
            $this->extractHrefLangTags($html)
        );
    }

    /**
     * An untranslated article leaves the default language as the only
     * candidate, and a single alternate is not rendered by the Core.
     */
    #[Test]
    public function untranslatedArticleGetsNoHrefLang(): void
    {
        $html = $this->renderDetail(1);

        self::assertSame([], $this->extractHrefLangTags($html));
    }

    /**
     * Without an article in the request the listener has to keep its hands
     * off - the list page must keep the hreflangs EXT:seo built for it.
     */
    #[Test]
    public function pageWithoutADetailPluginKeepsItsHrefLangs(): void
    {
        $html = $this->assertRendersWithoutError(
            $this->executeFrontendSubRequest((new InternalRequest())->withPageId(2))
        );

        self::assertSame(
            [
                'en-US' => 'http://localhost/list',
                'de-DE' => 'http://localhost/de/list',
                'fr-FR' => 'http://localhost/fr/list',
                'x-default' => 'http://localhost/list',
            ],
            $this->extractHrefLangTags($html)
        );
    }

    /**
     * The alternate set describes the article, not the visitor - so it has to
     * come out identical no matter which translation is being rendered.
     */
    #[Test]
    #[DataProvider('renderingLanguageProvider')]
    public function theHrefLangSetIsIndependentOfTheRenderedLanguage(string $uri): void
    {
        $html = $this->renderDetailAtUrl($uri, 10);

        self::assertSame(
            [
                'en-US' => 'http://localhost/detail',
                'de-DE' => 'http://localhost/de/detail',
                'fr-FR' => 'http://localhost/fr/detail',
                'x-default' => 'http://localhost/detail',
            ],
            $this->extractHrefLangTags($html)
        );
    }

    /**
     * ... and so does the removal of an article that must not be indexed,
     * even though the German translation is a record of its own.
     */
    #[Test]
    #[DataProvider('renderingLanguageProvider')]
    public function articleExcludedFromTheIndexGetsNoHrefLangInAnyLanguage(string $uri): void
    {
        $html = $this->renderDetailAtUrl($uri, 12);

        self::assertSame([], $this->extractHrefLangTags($html));
    }

    public static function renderingLanguageProvider(): array
    {
        return [
            'rendered in English' => ['http://localhost/detail'],
            'rendered in German' => ['http://localhost/de/detail'],
            'rendered in French' => ['http://localhost/fr/detail'],
        ];
    }
}
