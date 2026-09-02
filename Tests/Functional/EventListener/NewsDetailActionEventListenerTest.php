<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\EventListener;

use GeorgRinger\News\Controller\NewsController;
use GeorgRinger\News\Event\NewsDetailActionEvent;
use GeorgRinger\NewsSeo\Domain\Model\News;
use GeorgRinger\NewsSeo\EventListener\NewsDetailActionEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The listener turns the three SEO columns of an article into the robots meta
 * tag of the detail view, overruling whatever the page record asks for.
 */
class NewsDetailActionEventListenerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'georgringer/news',
        'georgringer/news-seo',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getMetaTagManager()->removeProperty('robots');
    }

    #[Test]
    #[DataProvider('robotsProvider')]
    public function robotsMetaTagIsWrittenFromTheArticle(bool $index, bool $follow, int $preview, string $expected): void
    {
        $news = new News();
        $news->setRobotsIndex($index);
        $news->setRobotsFollow($follow);
        $news->setMaxImagePreview($preview);

        $this->dispatch($news);

        self::assertSame($expected, $this->getMetaTagManager()->getProperty('robots')[0]['content'] ?? '');
    }

    public static function robotsProvider(): array
    {
        return [
            'indexed and followed' => [true, true, 0, 'index,follow'],
            'indexed with a standard preview' => [true, true, 1, 'index,follow,max-image-preview:standard'],
            'indexed with a large preview' => [true, true, 2, 'index,follow,max-image-preview:large'],
            'indexed but not followed' => [true, false, 0, 'index,nofollow'],
            'excluded from the index' => [false, false, 0, 'noindex,nofollow'],
        ];
    }

    /**
     * The listener has to keep its hands off anything that is not a news
     * model, because the detail action assigns other values as well.
     */
    #[Test]
    public function otherAssignedValuesAreIgnored(): void
    {
        $this->getMetaTagManager()->addProperty('robots', 'index,follow');

        (new NewsDetailActionEventListener())($this->buildEvent(null));

        self::assertSame('index,follow', $this->getMetaTagManager()->getProperty('robots')[0]['content'] ?? '');
    }

    private function dispatch(News $news): void
    {
        (new NewsDetailActionEventListener())($this->buildEvent($news));
    }

    /**
     * The event is final, so it cannot be mocked. Neither the controller nor
     * the request is read by the listener, and building a real NewsController
     * needs an Extbase request the test does not have - hence the bare
     * instance.
     */
    private function buildEvent(?News $news): NewsDetailActionEvent
    {
        $controller = (new \ReflectionClass(NewsController::class))->newInstanceWithoutConstructor();

        return new NewsDetailActionEvent($controller, ['newsItem' => $news], null);
    }

    private function getMetaTagManager(): object
    {
        return GeneralUtility::makeInstance(MetaTagManagerRegistry::class)->getManagerForProperty('robots');
    }
}
