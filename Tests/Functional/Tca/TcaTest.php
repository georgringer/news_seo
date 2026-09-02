<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\Tca;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Smoke tests proving that the columns news_seo adds to EXT:news survive a
 * full TCA build and stay reachable in the backend form.
 */
class TcaTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'georgringer/news',
        'georgringer/news-seo',
    ];

    #[Test]
    #[DataProvider('columnProvider')]
    public function columnIsAddedToNews(string $column): void
    {
        self::assertArrayHasKey($column, $GLOBALS['TCA']['tx_news_domain_model_news']['columns']);
    }

    #[Test]
    #[DataProvider('columnProvider')]
    public function columnIsShownInTheNewsForm(string $column): void
    {
        $showitem = $GLOBALS['TCA']['tx_news_domain_model_news']['types']['0']['showitem'];
        $palette = $GLOBALS['TCA']['tx_news_domain_model_news']['palettes']['newsseoindex']['showitem'];

        self::assertContains(
            $column,
            GeneralUtility::trimExplode(',', $showitem . ',' . $palette, true)
        );
    }

    public static function columnProvider(): array
    {
        return [
            'robots_index' => ['robots_index'],
            'robots_follow' => ['robots_follow'],
            'max_image_preview' => ['max_image_preview'],
            'canonical_link' => ['canonical_link'],
        ];
    }

    #[Test]
    public function robotsPaletteIsRegistered(): void
    {
        self::assertArrayHasKey('newsseoindex', $GLOBALS['TCA']['tx_news_domain_model_news']['palettes']);
    }

    /**
     * The fields are registered with "after:sitemap_priority". EXT:news keeps
     * that column inside its "sitemap" palette, so the Core appends the robots
     * palette behind that palette, on the metadata tab.
     */
    #[Test]
    public function paletteIsPlacedAfterTheSitemapPalette(): void
    {
        $showitem = $GLOBALS['TCA']['tx_news_domain_model_news']['types']['0']['showitem'];

        self::assertMatchesRegularExpression(
            '/--palette--;;sitemap,\s*--palette--;;newsseoindex,\s*canonical_link/s',
            $showitem
        );
        self::assertContains(
            'sitemap_priority',
            GeneralUtility::trimExplode(',', $GLOBALS['TCA']['tx_news_domain_model_news']['palettes']['sitemap']['showitem'], true)
        );
    }

    /**
     * Both flags default to 1, so an article is indexable unless an editor
     * says otherwise. Changing this would silently deindex existing content.
     */
    #[Test]
    #[DataProvider('robotsFlagProvider')]
    public function robotsFlagIsACheckboxDefaultingToOn(string $column): void
    {
        $config = $GLOBALS['TCA']['tx_news_domain_model_news']['columns'][$column]['config'];

        self::assertSame('check', $config['type']);
        self::assertSame(1, $config['default']);
    }

    public static function robotsFlagProvider(): array
    {
        return [
            'robots_index' => ['robots_index'],
            'robots_follow' => ['robots_follow'],
        ];
    }

    /**
     * The values are what GeorgRinger\NewsSeo\Domain\Model\News maps onto the
     * "max-image-preview" robots directive, so they must not drift.
     */
    #[Test]
    public function maxImagePreviewOffersTheThreeGoogleValues(): void
    {
        $config = $GLOBALS['TCA']['tx_news_domain_model_news']['columns']['max_image_preview']['config'];

        self::assertSame('select', $config['type']);
        self::assertSame('selectSingle', $config['renderType']);
        self::assertSame([0, 1, 2], array_column($config['items'], 'value'));
    }

    #[Test]
    public function canonicalLinkIsALinkFieldAcceptingNewsRecords(): void
    {
        $config = $GLOBALS['TCA']['tx_news_domain_model_news']['columns']['canonical_link']['config'];

        self::assertSame('link', $config['type']);
        self::assertSame(['page', 'url', 'tx_news'], $config['allowedTypes']);
    }

    /**
     * The robots flags describe the default language record; a translation
     * must not be able to drift away from it.
     */
    #[Test]
    #[DataProvider('robotsFlagProvider')]
    public function robotsFlagIsExcludedFromLocalization(string $column): void
    {
        self::assertSame('exclude', $GLOBALS['TCA']['tx_news_domain_model_news']['columns'][$column]['l10n_mode']);
    }
}
