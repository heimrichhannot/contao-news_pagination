<?php

namespace HeimrichHannot\NewsPagination;


use Contao\StringUtil;
use HeimrichHannot\Request\Request;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class Hooks extends \Controller
{
    protected static $manualPaginationFound = false;

    static $arrTags = [
        'p',
        'span',
        'strong',
        'i',
        'em',
        'div'
    ];

    public function addNewsPagination($objTemplate, $arrArticle, $objModule)
    {
        if (\Input::get('print'))
        {
            return;
        }

        if ($objModule->addManualPagination) {
            $this->doAddManualNewsPagination($objTemplate, $arrArticle, $objModule);
        }

        if (!static::$manualPaginationFound && $objModule->addPagination) {
            $this->doAddNewsPagination($objTemplate, $arrArticle, $objModule);
        }
    }

    public function doAddManualNewsPagination($objTemplate, $arrArticle, $objModule)
    {
        $intPage     = \Input::get('page_n' . $objModule->id) ?: 1;
        $intMaxIndex = 0;

        // add wrapper div since remove() called on root elements doesn't work (bug?)
        $objNode          = new HtmlPageCrawler('<div><div class="news-pagination-content">' . StringUtil::restoreBasicEntities($objTemplate->text) . '</div></div>');
        $objStartElements = $objNode->filter('.news-pagination-content > [class*="ce_news_pagination_start"]');

        if ($objStartElements->count() < 1) {
            return;
        }

        static::$manualPaginationFound = true;

        $objStartElements->each(
            function ($objElement) use ($intPage, &$intMaxIndex) {
                $intIndex = $objElement->getAttribute('data-index');

                if ($intIndex > $intMaxIndex) {
                    $intMaxIndex = $intIndex;
                }

                if ($intPage != $intIndex) {
                    $objElement->remove();
                }
            }
        );

        $objTemplate->text = str_replace(['%7B', '%7D'], ['{', '}'], $objNode->saveHTML());

        // add pagination
        $objPagination               =
            new \Pagination($intMaxIndex, 1, \Config::get('maxPaginationLinks'), 'page_n' . $objModule->id);
        $objTemplate->newsPagination = $objPagination->generate("\n  ");
    }

    public function doAddNewsPagination($objTemplate, $arrArticle, $objModule)
    {
        $intMaxAmount = $objModule->paginationMaxCharCount;
        $intPageCount = 1;

        // add wrapper div since remove() called on root elements doesn't work (bug?)
        $objNode              = new HtmlPageCrawler('<div><div class="news-pagination-content">' . StringUtil::restoreBasicEntities($objTemplate->text) . '</div></div>');
        $intTextAmount        = 0;
        $strCeTextCssSelector = $objModule->paginationCeTextCssSelector;
        $arrTags              = static::$arrTags;

        $intCurrentPage = Request::getGet('page_n' . $objModule->id);

        // replace multiple br elements to
        $objNode->filter('.news-pagination-content > [class*="ce_"]')->each(
            function ($objElement) use (&$intTextAmount, $intMaxAmount, $arrTags, $objNode, $strCeTextCssSelector) {
                if (strpos($objElement->getAttribute('class'), 'ce_text') !== false && strpos($objElement->html(), 'figure') === false) {
                    $objElement->children($strCeTextCssSelector . ', figure')->each(
                        function ($objParagraph) use (&$intTextAmount, $intMaxAmount, $arrTags) {
                            $objParagraph->html(preg_replace('@<br\s?\/?><br\s?\/?>@i', '</p><p>', $objParagraph->html()));
                        });
                }
            }
        );

        // pagination
        $objNode     = new HtmlPageCrawler($objNode->saveHTML());
        $arrElements = [];

        // get relevant elements
        $objNode->filter('.news-pagination-content > [class*="ce_"]')->each(
            function ($objElement) use (&$intTextAmount, $intMaxAmount, $arrTags, $objNode, $strCeTextCssSelector, &$intPageCount, &$arrElements) {
                if (strpos($objElement->getAttribute('class'), 'ce_text') !== false &&
                    strpos($objElement->html(), 'figure') === false
                ) {
                    if ($strCeTextCssSelector)
                    {
                        $objElement = $objElement->filter($strCeTextCssSelector);
                    }

                    $objElement->children()->each(
                        function ($objElement) use (&$intTextAmount, $intMaxAmount, $arrTags, &$intPageCount, &$arrElements) {
                            $arrElements[] = [
                                'element' => $objElement,
                                'text'    => $objElement->text(),
                                'tag'     => $objElement->nodeName(),
                                'length'  => strlen($objElement->text())
                            ];
                        }
                    );
                } else {
                    $arrElements[] = [
                        'element' => $objElement,
                        'text'    => $objElement->text(),
                        'tag'     => $objElement->nodeName(),
                        'length'  => 0
                    ];
                }
            }
        );

        // split array by text amounts
        $arrSplitted = [];

        foreach ($arrElements as $arrElement) {
            $intTextAmountOrigin = $intTextAmount;
            $intTextAmount       += $arrElement['length'];

            if ($intTextAmount > $intMaxAmount && $intTextAmountOrigin != 0) {
                $intPageCount++;
                $intTextAmount = $arrElement['length'];
            }

            if (!isset($arrSplitted[$intPageCount])) {
                $arrSplitted[$intPageCount] = [];
            }

            $arrSplitted[$intPageCount][] = $arrElement;
        }

        // hold together headlines and paragraphs
        $arrResult = [];

        foreach ($arrSplitted as $intPage => $arrParts) {
            $arrHeadlines    = [];
            $arrNonHeadlines = [];
            $blnTrailingHeadlines = true;

            for ($i = count($arrParts) - 1; $i > -1; $i--) {
                if ($blnTrailingHeadlines && in_array($arrParts[$i]['tag'], ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'h8', 'h9', 'h10'])) {
                    $arrHeadlines[] = $arrParts[$i];
                } else {
                    // break overlap handling if headline is not trailing
                    $blnTrailingHeadlines = false;
                    $arrNonHeadlines[] = $arrParts[$i];
                }
            }

            if (empty($arrResult[$intPage])) {
                $arrResult[$intPage] = array_reverse($arrNonHeadlines);
            } else {
                $arrResult[$intPage] = array_merge($arrResult[$intPage], array_reverse($arrNonHeadlines));
            }

            if (!empty($arrHeadlines))
            {
                if (empty($arrResult[$intPage + 1])) {
                    $arrResult[$intPage + 1] = array_reverse($arrHeadlines);
                } else {
                    $arrResult[$intPage + 1] = array_merge(array_reverse($arrHeadlines), $arrResult[$intPage + 1]);
                }
            }
        }

        foreach ($arrResult as $intPage => $arrParts) {
            foreach ($arrParts as $arrPart) {
                if ($intCurrentPage && is_numeric($intCurrentPage)) {
                    if ($intPage != $intCurrentPage) {
                        $arrPart['element']->remove();
                    }
                } else {
                    if ($intPage != 1) {
                        $arrPart['element']->remove();
                    }
                }
            }
        }

        $objTemplate->text = str_replace(['%7B', '%7D'], ['{', '}'], $objNode->saveHTML());

        // add pagination
        $objPagination               =
            new \Pagination($intPageCount, 1, \Config::get('maxPaginationLinks'), 'page_n' . $objModule->id);
        $objTemplate->newsPagination = $objPagination->generate("\n  ");
    }

    private static function removeNodeIfNecessary($intPage, &$intTextAmount, $intMaxAmount, $objElement, &$intPageCount)
    {
        if ($intTextAmount > $intMaxAmount)
        {
            $intPageCount++;
            $intTextAmount = strlen($objElement->text());
        }

        if ($intPage && is_numeric($intPage)) {
            if ($intPageCount != $intPage) {
                $objElement->remove();
            }
        } else {
            if ($intPageCount != 1) {
                $objElement->remove();
            }
        }
    }
}