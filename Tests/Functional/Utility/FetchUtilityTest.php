<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Functional\Utility;

use GeorgRinger\NewsSeo\Utility\FetchUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * FetchUtility reads the SEO columns straight from the database, bypassing
 * Extbase, because both event listeners run before a news model exists.
 */
class FetchUtilityTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/News.csv');
    }

    #[Test]
    #[DataProvider('isNoIndexProvider')]
    public function isNoIndex(int $newsId, bool $expected): void
    {
        self::assertSame($expected, FetchUtility::isNoIndex($newsId));
    }

    public static function isNoIndexProvider(): array
    {
        return [
            'robots_index=1 is indexed' => [1, false],
            'robots_index=0 is not indexed' => [2, true],
            // Nothing to index means nothing to link to either, so an unknown
            // uid has to be treated like an excluded record.
            'unknown record is not indexed' => [99, true],
        ];
    }

    #[Test]
    public function getRowReturnsTheSeoColumns(): void
    {
        self::assertSame(
            [
                'uid' => 1,
                'title' => 'Indexed article',
                'sys_language_uid' => 0,
                'robots_index' => 1,
                'canonical_link' => '',
            ],
            $this->normalize(FetchUtility::getRow(1))
        );
    }

    #[Test]
    public function getRowReturnsTheCanonicalLink(): void
    {
        self::assertSame('t3://page?uid=42', FetchUtility::getRow(3)['canonical_link']);
    }

    /**
     * An unknown uid makes fetchAssociative() return false. The method
     * promises an array, and an empty one is the only honest answer - callers
     * have to be able to use empty() or count() on the result. See issue #43.
     */
    #[Test]
    public function getRowReturnsAnEmptyArrayForAnUnknownRecord(): void
    {
        self::assertSame([], FetchUtility::getRow(99));
    }

    /**
     * The two listeners read the columns defensively, so the shape of the
     * result must not offer anything that looks like SEO data either.
     */
    #[Test]
    public function getRowCarriesNoSeoDataForAnUnknownRecord(): void
    {
        $row = FetchUtility::getRow(99);

        self::assertArrayNotHasKey('robots_index', $row);
        self::assertArrayNotHasKey('canonical_link', $row);
    }

    /**
     * SQLite hands back integer columns as strings, MySQL and PostgreSQL do
     * not. The utility does not cast, so the test compares on normalized data
     * instead of pinning one DBMS.
     */
    private function normalize(array $row): array
    {
        foreach (['uid', 'sys_language_uid', 'robots_index'] as $column) {
            $row[$column] = (int)$row[$column];
        }

        return $row;
    }
}
