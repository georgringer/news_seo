<?php

declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Tests\Unit\Domain\Model;

use GeorgRinger\NewsSeo\Domain\Model\News;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The class body of this model is merged into
 * GeorgRinger\News\Domain\Model\News by the ClassCacheManager of EXT:news.
 * These tests cover the accessors in isolation; that the merge actually
 * happens is proven by the functional NewsClassMergeTest.
 */
class NewsTest extends UnitTestCase
{
    protected News $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new News();
    }

    #[Test]
    public function robotsAreNotIndexedByDefault(): void
    {
        self::assertFalse($this->subject->isRobotsIndex());
        self::assertFalse($this->subject->isRobotsFollow());
        self::assertSame(0, $this->subject->getMaxImagePreview());
    }

    #[Test]
    public function setRobotsIndex(): void
    {
        $this->subject->setRobotsIndex(true);

        self::assertTrue($this->subject->isRobotsIndex());
    }

    #[Test]
    public function setRobotsFollow(): void
    {
        $this->subject->setRobotsFollow(true);

        self::assertTrue($this->subject->isRobotsFollow());
    }

    #[Test]
    public function setMaxImagePreview(): void
    {
        $this->subject->setMaxImagePreview(2);

        self::assertSame(2, $this->subject->getMaxImagePreview());
    }

    #[Test]
    #[DataProvider('setterReturnsTheModelProvider')]
    public function setterReturnsTheModel(string $method, bool|int $value): void
    {
        self::assertSame($this->subject, $this->subject->{$method}($value));
    }

    public static function setterReturnsTheModelProvider(): array
    {
        return [
            'setRobotsIndex' => ['setRobotsIndex', true],
            'setRobotsFollow' => ['setRobotsFollow', true],
            'setMaxImagePreview' => ['setMaxImagePreview', 1],
        ];
    }

    /**
     * The values are the ones offered by the TCA select, mapped onto the
     * robots directive documented at
     * https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag#max-image-preview
     */
    #[Test]
    #[DataProvider('maxImagePreviewStringProvider')]
    public function getMaxImagePreviewString(int $value, string $expected): void
    {
        $this->subject->setMaxImagePreview($value);

        self::assertSame($expected, $this->subject->getMaxImagePreviewString());
    }

    public static function maxImagePreviewStringProvider(): array
    {
        return [
            'none' => [0, ''],
            'standard' => [1, 'max-image-preview:standard'],
            'large' => [2, 'max-image-preview:large'],
            'unknown value is not rendered' => [99, ''],
        ];
    }
}
