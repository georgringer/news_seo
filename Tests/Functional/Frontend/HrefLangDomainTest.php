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

/**
 * The same site, but with each language on its own host instead of a path
 * prefix.
 *
 * ModifyHrefLangEventListener::getAbsoluteUrl() absolutizes every alternate
 * with the base of the language that is *currently being rendered*, not with
 * the base of the language the alternate points at. That only stays correct
 * because the language menu emits an absolute URL as soon as the host
 * differs, so the rewrite never touches a cross-host link. This test pins
 * that: if the link ever arrives host-less, every alternate would collapse
 * onto the host of the current language.
 */
class HrefLangDomainTest extends AbstractFrontendTestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    protected function siteLanguages(): array
    {
        return [
            [
                'title' => 'English',
                'enabled' => true,
                'languageId' => 0,
                'base' => 'http://localhost/',
                'locale' => 'en_US.UTF-8',
                'hreflang' => 'en-US',
                'navigationTitle' => 'English',
                'flag' => 'us',
            ],
            [
                'title' => 'German',
                'enabled' => true,
                'languageId' => 1,
                'base' => 'http://de.localhost/',
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
                'base' => 'http://fr.localhost/',
                'locale' => 'fr_FR.UTF-8',
                'hreflang' => 'fr-FR',
                'navigationTitle' => 'Francais',
                'flag' => 'fr',
                'fallbackType' => 'strict',
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderingLanguageProvider')]
    public function everyAlternateKeepsItsOwnHost(string $uri): void
    {
        $html = $this->renderDetailAtUrl($uri, 10);

        self::assertSame(
            [
                'en-US' => 'http://localhost/detail',
                'de-DE' => 'http://de.localhost/detail',
                'fr-FR' => 'http://fr.localhost/detail',
                'x-default' => 'http://localhost/detail',
            ],
            $this->extractHrefLangTags($html)
        );
    }

    #[Test]
    #[DataProvider('renderingLanguageProvider')]
    public function canonicalStaysOnTheHostBeingRendered(string $uri): void
    {
        $html = $this->renderDetailAtUrl($uri, 10);

        self::assertSame([rtrim($uri, '?')], $this->extractLinkTag($html, 'canonical'));
    }

    public static function renderingLanguageProvider(): array
    {
        return [
            'rendered in English' => ['http://localhost/detail'],
            'rendered in German' => ['http://de.localhost/detail'],
            'rendered in French' => ['http://fr.localhost/detail'],
        ];
    }

    /**
     * Dropping a language must not disturb the hosts of the remaining ones.
     */
    #[Test]
    public function droppingALanguageLeavesTheOtherHostsAlone(): void
    {
        $html = $this->renderDetailAtUrl('http://de.localhost/detail', 11);

        self::assertSame(
            [
                'en-US' => 'http://localhost/detail',
                'de-DE' => 'http://de.localhost/detail',
                'x-default' => 'http://localhost/detail',
            ],
            $this->extractHrefLangTags($html)
        );
    }
}
