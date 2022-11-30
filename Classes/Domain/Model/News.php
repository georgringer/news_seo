<?php
declare(strict_types=1);

namespace GeorgRinger\NewsSeo\Domain\Model;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */


/**
 * Persist dynamic data of import
 */
class News extends \GeorgRinger\News\Domain\Model\News
{

    /** @var bool */
    protected $noIndex = false;

    /** @var bool */
    protected $noFollow = false;

    /** @var int */
    protected $maxImagePreview = 0;

    /**
     * @return bool
     */
    public function isNoIndex(): bool
    {
        return $this->noIndex;
    }

    /**
     * @param bool $noIndex
     * @return News
     */
    public function setNoIndex(bool $noIndex): News
    {
        $this->noIndex = $noIndex;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNoFollow(): bool
    {
        return $this->noFollow;
    }

    /**
     * @param bool $noFollow
     * @return News
     */
    public function setNoFollow(bool $noFollow): News
    {
        $this->noFollow = $noFollow;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxImagePreview(): int
    {
        return $this->maxImagePreview;
    }

    public function getMaxImagePreviewString(): string
    {
        $mapping = [
            1 => 'max-image-preview:standard',
            2 => 'max-image-preview:large',
        ];
        return $mapping[$this->maxImagePreview] ?? '';
    }

    /**
     * @param int $maxImagePreview
     * @return News
     */
    public function setMaxImagePreview(int $maxImagePreview): News
    {
        $this->maxImagePreview = $maxImagePreview;
        return $this;
    }
}
