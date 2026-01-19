<?php
declare(strict_types=1);

namespace GeorgRinger\NewsSeo\EventListener;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use GeorgRinger\NewsSeo\Utility\FetchUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;

class ModifyUrlForCanonicalTagEventListener
{

    protected PageRepository $pageRepository;

    public function __construct(?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
    }

    public function __invoke(ModifyUrlForCanonicalTagEvent $event): void
    {
        $href = $event->getUrl();

        $newsId = $this->getNewsId();
        if (!$newsId) {
            return;
        }

        $row = FetchUtility::getRow($newsId);
        if (!($row['robots_index'] ?? false) && !($row['canonical_link'] ?? false)) {
            return;
        }

        if ($row['canonical_link'] ?? false) {
            $href = $this->checkCanonicalLink($event->getRequest(), $row['canonical_link']);
        }

        if (!$href) {
            $href = $this->checkDefaultCanonical();
        }

        if (!empty($href)) {
            $event->setUrl($href);
        }
    }

    protected function checkCanonicalLink(ServerRequestInterface $request, string $configuration): string
    {
        $typoScriptFrontendController = $request->getAttribute('frontend.controller');
        $pageRecord = $request->getAttribute('frontend.page.information')->getPageRecord();
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $typoScriptFrontendController);
        $cObj->setRequest($request);
        $cObj->start($pageRecord, 'pages');
        return $cObj->createUrl([
            'parameter' => $configuration,
            'forceAbsoluteUrl' => true,
        ]);
    }

    /**
     * @see \TYPO3\CMS\Seo\Canonical\CanonicalGenerator::checkDefaultCanonical()
     */
    protected function checkDefaultCanonical(): string
    {
        $request = $this->getRequest();
        $pageInformation = $request->getAttribute('frontend.page.information');
        $id = $pageInformation->getId();
        // We should only create a canonical link to the target, if the target is within a valid site root
        $inSiteRoot = $this->isPageWithinSiteRoot($id);
        if (!$inSiteRoot) {
            return '';
        }

        // Temporarily remove current mount point information as we want to have the
        // URL of the target page and not of the page within the mount point if the
        // current page is a mount point.
        $pageInformation = clone $pageInformation;
        $pageInformation->setMountPoint('');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $request->getAttribute('frontend.controller'));
        $cObj->setRequest($request);
        $cObj->start($pageInformation->getPageRecord(), 'pages');
        return $cObj->createUrl([
            'parameter' => $id . ',' . $request->getAttribute('routing')->getPageType(),
            'forceAbsoluteUrl' => true,
            'addQueryString' => true,
            'addQueryString.' => [
                'exclude' => implode(
                    ',',
                    CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
                        $id,
                        (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']
                    )
                ),
            ],
        ]);
    }

    protected function isPageWithinSiteRoot(int $id): bool
    {
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $id)->get();
        foreach ($rootline as $page) {
            if ($page['is_siteroot']) {
                return true;
            }
        }
        return false;
    }

    protected function getNewsId(): int
    {
        $newsId = 0;
        $request = $this->getRequest();
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing');
        if (isset($pageArguments->getRouteArguments()['tx_news_pi1']['news'])) {
            $newsId = (int)$pageArguments->getRouteArguments()['tx_news_pi1']['news'];
        } elseif (isset($request->getQueryParams()['tx_news_pi1']['news'])) {
            $newsId = (int)$request->getQueryParams()['tx_news_pi1']['news'];
        }

        return $newsId;
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    protected function getPageId()
    {
        return $this->getRequest()->getAttribute('routing')->getPageId();
    }

    protected function getPageType()
    {
        return $this->getRequest()->getAttribute('routing')->getPageType();
    }
}
