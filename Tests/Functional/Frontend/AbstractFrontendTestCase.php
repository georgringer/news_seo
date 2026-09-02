<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\Frontend;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Base for the tests that render a real news detail page and look at <head>.
 *
 * Every feature of this extension only exists in the rendered markup: a robots
 * meta tag, a canonical link, an hreflang set. Asserting the listeners in
 * isolation cannot show whether the Core still emits its own page-level tag
 * afterwards and overwrites ours, which is exactly the kind of regression a
 * TYPO3 minor can introduce.
 *
 * The setup follows EXT:news' own Tests/Functional/Frontend/AbstractFrontendTestCase.
 */
abstract class AbstractFrontendTestCase extends FunctionalTestCase
{
    protected const ROOT_PAGE_ID = 1;

    protected const DETAIL_PAGE_ID = 3;

    protected const NOINDEX_DETAIL_PAGE_ID = 4;

    /** Its French translation carries a canonical_link. */
    protected const CANONICAL_DETAIL_PAGE_ID = 5;

    /**
     * fluid_styled_content is needed because Extbase registers the TypoScript
     * for tt_content.news_* through "defaultContentRendering", which is only
     * included when FE/contentRenderingTemplates is set - and that is what
     * fluid_styled_content does. seo brings the canonical generator.
     */
    protected array $coreExtensionsToLoad = [
        'fluid',
        'fluid_styled_content',
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'georgringer/news',
        'georgringer/news-seo',
    ];

    /**
     * tx_news_pi1[news] is not exempt from the cHash check. Without this every
     * test would have to compute a valid cHash.
     */
    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'enforceValidation' => false,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/news.csv');

        $this->writeSiteConfiguration();
        $this->setUpFrontendRootPage(self::ROOT_PAGE_ID, [
            'constants' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                'EXT:news/Configuration/TypoScript/constants.typoscript',
            ],
            'setup' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                'EXT:news/Configuration/TypoScript/setup.typoscript',
                'EXT:news_seo/Tests/Functional/Frontend/Fixtures/TypoScript/frontend.typoscript',
            ],
        ]);
    }

    /**
     * Renders the detail plugin for one article. The uid travels in
     * tx_news_pi1[news], which is both what Extbase maps onto the action
     * argument and what the two SEO listeners read off the request.
     */
    protected function renderDetail(int $newsId, int $pageId = self::DETAIL_PAGE_ID): string
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId($pageId)
                ->withQueryParameters(['tx_news_pi1[news]' => $newsId])
        );

        return $this->assertRendersWithoutError($response);
    }

    /**
     * Same, but for a request that has to resolve through a site language
     * base - the only way to reach a translation, since the legacy "L"
     * parameter no longer selects a language.
     */
    protected function renderDetailAtUrl(string $uri, int $newsId): string
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri))->withQueryParameters(['tx_news_pi1[news]' => $newsId])
        );

        return $this->assertRendersWithoutError($response);
    }

    protected function assertRendersWithoutError(ResponseInterface $response): string
    {
        self::assertSame(200, $response->getStatusCode());

        $html = (string)$response->getBody();
        self::assertStringNotContainsString('Oops, an error occurred', $html);
        self::assertStringNotContainsString('Whoops, looks like something went wrong', $html);
        self::assertNotSame('', trim($html));

        return $html;
    }

    /**
     * @return list<string> the content attribute of every meta tag with that name
     */
    protected function extractMetaTag(string $html, string $name): array
    {
        preg_match_all(
            sprintf('#<meta[^>]+name="%s"[^>]+content="([^"]*)"#i', preg_quote($name, '#')),
            $html,
            $matches
        );

        return $matches[1];
    }

    /**
     * @return list<string> the href of every <link rel="..."> with that rel
     */
    protected function extractLinkTag(string $html, string $rel): array
    {
        preg_match_all(
            sprintf('#<link[^>]+rel="%s"[^>]+href="([^"]*)"#i', preg_quote($rel, '#')),
            $html,
            $matches
        );

        return $matches[1];
    }

    /**
     * @return array<string, string> hreflang => href, in document order
     */
    protected function extractHrefLangTags(string $html): array
    {
        preg_match_all(
            '#<link[^>]+rel="alternate"[^>]+hreflang="([^"]*)"[^>]+href="([^"]*)"#i',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $tags = [];
        foreach ($matches as $match) {
            $tags[$match[1]] = $match[2];
        }

        return $tags;
    }

    protected function writeSiteConfiguration(): void
    {
        $path = $this->instancePath . '/typo3conf/sites/testing';
        GeneralUtility::mkdir_deep($path);

        file_put_contents($path . '/config.yaml', Yaml::dump([
            'rootPageId' => self::ROOT_PAGE_ID,
            'base' => 'http://localhost/',
            'languages' => $this->siteLanguages(),
        ], 99, 2));
    }

    /**
     * Path-based languages on a single host. Overridden by the test that
     * covers host-based ones.
     *
     * Both translations use fallbackType "strict" on purpose: that is the
     * only mode in which EXT:news' NewsAvailability drops a language, and
     * news_seo runs the same check afterwards.
     *
     * @return list<array<string, mixed>>
     */
    protected function siteLanguages(): array
    {
        return [
            [
                'title' => 'English',
                'enabled' => true,
                'languageId' => 0,
                'base' => '/',
                'locale' => 'en_US.UTF-8',
                'hreflang' => 'en-US',
                'navigationTitle' => 'English',
                'flag' => 'us',
            ],
            [
                'title' => 'German',
                'enabled' => true,
                'languageId' => 1,
                'base' => '/de/',
                'locale' => 'de_DE.UTF-8',
                'hreflang' => 'de-DE',
                'navigationTitle' => 'Deutsch',
                'flag' => 'de',
                'fallbackType' => 'strict',
            ],
            [
                'title' => 'French',
                'enabled' => true,
                'languageId' => 2,
                'base' => '/fr/',
                'locale' => 'fr_FR.UTF-8',
                'hreflang' => 'fr-FR',
                'navigationTitle' => 'Francais',
                'flag' => 'fr',
                'fallbackType' => 'strict',
            ],
        ];
    }
}
