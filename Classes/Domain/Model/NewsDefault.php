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
class NewsDefault extends \GeorgRinger\News\Domain\Model\NewsDefault
{

    /** @var bool */
    protected $robotsIndex = false;

    /** @var bool */
    protected $robotsFollow = false;

    /** @var int */
    protected $maxImagePreview = 0;

    /**
     * @return bool
     */
    public function isRobotsIndex(): bool
    {
        return $this->robotsIndex;
    }

    /**
     * @param bool $robotsIndex
     * @return News
     */
    public function setRobotsIndex(bool $robotsIndex): News
    {
        $this->robotsIndex = $robotsIndex;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRobotsFollow(): bool
    {
        return $this->robotsFollow;
    }

    /**
     * @param bool $robotsFollow
     * @return News
     */
    public function setRobotsFollow(bool $robotsFollow): News
    {
        $this->robotsFollow = $robotsFollow;
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
