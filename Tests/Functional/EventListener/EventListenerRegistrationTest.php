<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\EventListener;

use GeorgRinger\News\Event\NewsDetailActionEvent;
use GeorgRinger\NewsSeo\EventListener\ModifyHrefLangEventListener;
use GeorgRinger\NewsSeo\EventListener\ModifyUrlForCanonicalTagEventListener;
use GeorgRinger\NewsSeo\EventListener\NewsDetailActionEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The whole extension is wired through three PSR-14 listeners declared in
 * Configuration/Services.yaml. A renamed event class or a typo in the yaml
 * disables a feature without any error, so the registration is asserted here.
 */
class EventListenerRegistrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'georgringer/news',
        'georgringer/news-seo',
    ];

    #[Test]
    #[DataProvider('listenerProvider')]
    public function listenerIsRegisteredForItsEvent(string $event, string $identifier, string $listener): void
    {
        $definitions = $this->getListenerProvider()->getAllListenerDefinitions();

        self::assertArrayHasKey($event, $definitions);
        self::assertArrayHasKey($identifier, $definitions[$event]);
        self::assertSame($listener, $definitions[$event][$identifier]['service']);
    }

    public static function listenerProvider(): array
    {
        return [
            'news detail action' => [
                NewsDetailActionEvent::class,
                'newsseo-newsdetailaction',
                NewsDetailActionEventListener::class,
            ],
            'canonical tag' => [
                ModifyUrlForCanonicalTagEvent::class,
                'newsseo-newsdetailcanonicalaction',
                ModifyUrlForCanonicalTagEventListener::class,
            ],
            'hreflang tags' => [
                ModifyHrefLangTagsEvent::class,
                'ext-seonews/modify-hreflang',
                ModifyHrefLangEventListener::class,
            ],
        ];
    }

    /**
     * EXT:news builds the hreflang list for the article; news_seo then filters
     * it. Running in the other order would leave the page hreflangs in place.
     */
    #[Test]
    public function hrefLangListenerRunsAfterTheOneOfNews(): void
    {
        $identifiers = array_keys(
            $this->getListenerProvider()->getAllListenerDefinitions()[ModifyHrefLangTagsEvent::class]
        );

        self::assertContains('ext-news/modify-hreflang', $identifiers);
        self::assertGreaterThan(
            array_search('ext-news/modify-hreflang', $identifiers, true),
            array_search('ext-seonews/modify-hreflang', $identifiers, true)
        );
    }

    #[Test]
    #[DataProvider('listenerProvider')]
    public function listenerCanBeBuiltFromTheContainer(string $event, string $identifier, string $listener): void
    {
        self::assertInstanceOf($listener, $this->get($listener));
    }

    private function getListenerProvider(): ListenerProvider
    {
        return $this->get(ListenerProvider::class);
    }
}
