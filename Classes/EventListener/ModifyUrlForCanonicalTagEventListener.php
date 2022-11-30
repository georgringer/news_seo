<?php
declare(strict_types=1);

namespace GeorgRinger\NewsSeo\EventListener;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;

class ModifyUrlForCanonicalTagEventListener
{

    public function __construct(TypoScriptFrontendController $typoScriptFrontendController = null, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->typoScriptFrontendController = $typoScriptFrontendController ?? $this->getTypoScriptFrontendController();
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
    }

    public function __invoke(ModifyUrlForCanonicalTagEvent $event): void
    {
        $href = $event->getUrl();
        if (!empty($href) || (int)$this->typoScriptFrontendController->page['no_index'] === 0) {
            return;
        }

        $newsId = $this->getNewsId();
        if (!$newsId) {
            return;
        }

        if ($this->isNoIndex($newsId)) {
            return;
        }

        $href = $this->checkDefaultCanonical();

        if (!empty($href)) {
            $event->setUrl($href);
        }
    }

    protected function isNoIndex(int $newsId): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_news_domain_model_news');
        $row = $queryBuilder->select('uid', 'no_index')
            ->from('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();

        return (bool)$row['no_index'];
    }

    protected function checkDefaultCanonical(): string
    {
        // We should only create a canonical link to the target, if the target is within a valid site root
        $inSiteRoot = $this->isPageWithinSiteRoot((int)$this->typoScriptFrontendController->id);
        if (!$inSiteRoot) {
            return '';
        }

        // Temporarily remove current mountpoint information as we want to have the
        // URL of the target page and not of the page within the mountpoint if the
        // current page is a mountpoint.
        $previousMp = $this->typoScriptFrontendController->MP;
        $this->typoScriptFrontendController->MP = '';

        $link = $this->typoScriptFrontendController->cObj->typoLink_URL([
            'parameter' => $this->typoScriptFrontendController->id . ',' . $this->typoScriptFrontendController->type,
            'forceAbsoluteUrl' => true,
            'addQueryString' => true,
            'addQueryString.' => [
                'method' => 'GET',
                'exclude' => implode(
                    ',',
                    CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
                        (int)$this->typoScriptFrontendController->id,
                        (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']
                    )
                ),
            ],
        ]);
        $this->typoScriptFrontendController->MP = $previousMp;
        return $link;
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

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
