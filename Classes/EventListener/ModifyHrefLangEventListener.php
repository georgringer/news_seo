<?php
declare(strict_types=1);

namespace GeorgRinger\NewsSeo\EventListener;

/**
 * This file is part of the "news_seo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use GeorgRinger\News\Seo\NewsAvailability;
use GeorgRinger\NewsSeo\Utility\FetchUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;

class ModifyHrefLangEventListener
{

    /** @var ContentObjectRenderer */
    public $cObj;

    /** @var LanguageMenuProcessor */
    protected $languageMenuProcessor;

    public function __construct(ContentObjectRenderer $cObj, LanguageMenuProcessor $languageMenuProcessor)
    {
        $this->cObj = $cObj;
        $this->languageMenuProcessor = $languageMenuProcessor;
    }

    public function __invoke(ModifyHrefLangTagsEvent $event): void
    {
        if ((int)$this->getTypoScriptFrontendController()->page['no_index'] === 0) {
            return;
        }

        $newsAvailabilityChecker = GeneralUtility::makeInstance(NewsAvailability::class);
        $newsId = $this->getNewsIdFromRequest();
        if ($newsId > 0) {
            if (FetchUtility::isNoIndex($newsId)) {
                return;
            }
            if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() > 10) {
                $this->cObj->setRequest($event->getRequest());
            }
            $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
            $site = $this->getTypoScriptFrontendController()->getSite();
            $siteLanguage = $this->getTypoScriptFrontendController()->getLanguage();
            $pageId = (int)$this->getTypoScriptFrontendController()->id;
            $allHrefLangs = [];
            foreach ($languages['languagemenu'] as $language) {
                if (!empty($language['link']) && $language['hreflang']) {
                    $page = $this->getTranslatedPageRecord($pageId, $language['languageId'], $site);
                    // do not set hreflang if a page is not translated explicitly
                    if (empty($page)) {
                        continue;
                    }
                    // do not set hreflang when canonical is set explicitly
                    if (!empty($page['canonical_link'])) {
                        continue;
                    }

                    $href = $this->getAbsoluteUrl($language['link'], $siteLanguage);
                    $allHrefLangs[$language['hreflang']] = $href;
                }
            }

            if (count($allHrefLangs) > 1) {
                if (array_key_exists($languages['languagemenu'][0]['hreflang'], $allHrefLangs)) {
                    $allHrefLangs['x-default'] = $allHrefLangs[$languages['languagemenu'][0]['hreflang']];
                }
            }


            $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
            $errorTriggered = false;
            foreach ($languages['languagemenu'] as $language) {
                $hreflangKey = $language['hreflang'];
                // skip all languages which are not used in hreflang
                if (!isset($allHrefLangs[$hreflangKey]) || $hreflangKey === 'x-default') {
                    continue;
                }

                try {
                    $check = $newsAvailabilityChecker->check($language['languageId']);

                    if (!$check) {
                        unset($allHrefLangs[$hreflangKey]);
                    }
                } catch (\UnexpectedValueException $e) {
                    $errorTriggered = true;
                }
            }

            if (!$errorTriggered) {
                if (count($allHrefLangs) <= 2) {
                    unset($allHrefLangs['x-default']);
                }
                $event->setHrefLangs($allHrefLangs);
            }
        }
    }

    protected function getTranslatedPageRecord(int $pageId, int $languageId, SiteInterface $site): array
    {
        $targetSiteLanguage = $site->getLanguageById($languageId);
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($targetSiteLanguage);

        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', $languageAspect);

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        if ($languageId > 0) {
            return $pageRepository->getPageOverlay($pageId, $languageId);
        }
        return $pageRepository->getPage($pageId);
    }

    /**
     * @param string $url
     * @param SiteLanguage $siteLanguage
     * @return string
     */
    protected function getAbsoluteUrl(string $url, SiteLanguage $siteLanguage): string
    {
        $uri = new Uri($url);
        if (empty($uri->getHost())) {
            $url = $siteLanguage->getBase()->withPath($uri->getPath());

            if ($uri->getQuery()) {
                $url = $url->withQuery($uri->getQuery());
            }
        }

        return (string)$url;
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.controller', $GLOBALS['TSFE']);
    }

    protected function getNewsIdFromRequest(): int
    {
        $newsId = 0;
        /** @var PageArguments $pageArguments */
        $pageArguments = $this->getRequest()->getAttribute('routing');
        if (isset($pageArguments, $pageArguments->getRouteArguments()['tx_news_pi1']['news'])) {
            $newsId = (int)$pageArguments->getRouteArguments()['tx_news_pi1']['news'];
        } elseif (isset($this->getRequest()->getQueryParams()['tx_news_pi1']['news'])) {
            $newsId = (int)$this->getRequest()->getQueryParams()['tx_news_pi1']['news'];
        }
        return $newsId;
    }
    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
