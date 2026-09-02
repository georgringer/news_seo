<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\Domain\Model;

use GeorgRinger\News\Utility\ClassCacheManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * news_seo does not subclass the EXT:news model. It registers itself in
 * $TYPO3_CONF_VARS['EXT']['news']['classes'] and lets the ClassCacheManager of
 * EXT:news concatenate Classes/Domain/Model/News.php into
 * GeorgRinger\News\Domain\Model\News, which the news class loader then
 * requires instead of the shipped file.
 *
 * Everything else in this extension - the detail action listener above all -
 * stands on that generated class, and it is the piece that silently breaks
 * when EXT:news reworks its class cache. The generated code is therefore
 * asserted directly: inside a single PHPUnit process the real model class is
 * already loaded by Composer before EXT:news can register its autoloader, so
 * calling the accessors on it would prove nothing.
 */
class NewsClassMergeTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'georgringer/news',
        'georgringer/news-seo',
    ];

    private ?string $mergedClass = null;

    #[Test]
    public function extensionIsRegisteredForTheNewsModel(): void
    {
        self::assertContains(
            'news_seo',
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['news']['classes']['Domain/Model/News'] ?? []
        );
    }

    #[Test]
    #[DataProvider('accessorProvider')]
    public function accessorIsMergedIntoTheNewsModel(string $signature): void
    {
        self::assertStringContainsString($signature, $this->buildMergedClass());
    }

    public static function accessorProvider(): array
    {
        return [
            'isRobotsIndex' => ['public function isRobotsIndex(): bool'],
            'setRobotsIndex' => ['public function setRobotsIndex(bool $robotsIndex): News'],
            'isRobotsFollow' => ['public function isRobotsFollow(): bool'],
            'setRobotsFollow' => ['public function setRobotsFollow(bool $robotsFollow): News'],
            'getMaxImagePreview' => ['public function getMaxImagePreview(): int'],
            'setMaxImagePreview' => ['public function setMaxImagePreview(int $maxImagePreview): News'],
            'getMaxImagePreviewString' => ['public function getMaxImagePreviewString(): string'],
        ];
    }

    #[Test]
    #[DataProvider('propertyProvider')]
    public function propertyIsMergedIntoTheNewsModel(string $property): void
    {
        self::assertStringContainsString('protected $' . $property . ' =', $this->buildMergedClass());
    }

    public static function propertyProvider(): array
    {
        return [
            'robotsIndex' => ['robotsIndex'],
            'robotsFollow' => ['robotsFollow'],
            'maxImagePreview' => ['maxImagePreview'],
        ];
    }

    /**
     * The setters name "News" as their return type without a namespace. That
     * is only correct because the generated file lives in the
     * GeorgRinger\News\Domain\Model namespace - which is also why Rector must
     * not import class names in Classes/Domain/Model/News.php.
     */
    #[Test]
    public function mergedClassKeepsTheNewsNamespace(): void
    {
        $code = $this->buildMergedClass();

        self::assertStringContainsString('namespace GeorgRinger\News\Domain\Model;', $code);
        self::assertStringNotContainsString('GeorgRinger\NewsSeo\Domain\Model', $code);
    }

    #[Test]
    public function mergedClassIsSyntacticallyValid(): void
    {
        $file = $this->instancePath . '/typo3temp/var/transient/MergedNewsModel.php';
        file_put_contents($file, $this->buildMergedClass());

        $output = [];
        $exitCode = 0;
        exec(sprintf('php -l %s 2>&1', escapeshellarg($file)), $output, $exitCode);

        self::assertSame(0, $exitCode, implode(LF, $output));
    }

    /**
     * The generated file is a complete PHP file, including the opening tag and
     * the namespace declaration.
     */
    private function buildMergedClass(): string
    {
        if ($this->mergedClass === null) {
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('news');
            (new ClassCacheManager($cache))->reBuild();
            $this->mergedClass = $cache->get('tx_news_domain_model_news');
        }

        return $this->mergedClass;
    }
}
