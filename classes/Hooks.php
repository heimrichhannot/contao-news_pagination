<?php

namespace HeimrichHannot\NewsPagination;


use HeimrichHannot\Request\Request;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class Hooks extends \Controller
{
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
        if ($objModule->addManualPagination)
        {
            $this->doAddManualNewsPagination($objTemplate, $arrArticle, $objModule);
        }

        if ($objModule->addPagination)
        {
            $this->doAddNewsPagination($objTemplate, $arrArticle, $objModule);
        }
    }

    public function doAddManualNewsPagination($objTemplate, $arrArticle, $objModule)
    {
        $intPage = \Input::get('page_n' . $objModule->id) ?: 1;
        $intMaxIndex = 0;

        // add wrapper div since remove() called on root elements doesn't work (bug?)
        $objNode          = new HtmlPageCrawler('<div><div class="news-pagination-content">' . $objTemplate->text . '</div></div>');
        $objStartElements = $objNode->filter('.news-pagination-content > [class*="ce_news_pagination_start"]');

        if ($objStartElements->count() < 1)
        {
            return;
        }

        $objStartElements->each(
            function ($objElement) use ($intPage, &$intMaxIndex)
            {
                $intIndex = $objElement->getAttribute('data-index');

                if ($intIndex > $intMaxIndex)
                {
                    $intMaxIndex = $intIndex;
                }

                if ($intPage != $intIndex)
                {
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

        // add wrapper div since remove() called on root elements doesn't work (bug?)
        $objNode              = new HtmlPageCrawler('<div><div class="news-pagination-content">' . $objTemplate->text . '</div></div>');
        $intTextAmount        = 0;
        $strCeTextCssSelector = $objModule->paginationCeTextCssSelector ? $objModule->paginationCeTextCssSelector . ' > *' : '*';
        $arrTags              = static::$arrTags;

        $intPage = Request::getGet('page_n' . $objModule->id);

        $objNode->filter('.news-pagination-content > [class*="ce_"]')->each(
            function ($objElement) use (&$intTextAmount, $intMaxAmount, $intPage, $arrTags, $objNode, $strCeTextCssSelector)
            {
                if (strpos($objElement->getAttribute('class'), 'ce_text') !== false && strpos($objElement->html(), 'figure') === false)
                {
                    $objElement->filter($strCeTextCssSelector . ', figure')->each(
                        function ($objParagraph) use (&$intTextAmount, $intMaxAmount, $intPage, $arrTags)
                        {
                            // replace multiple br elements to
                            $objParagraph->html(preg_replace('@<br\s?\/?><br\s?\/?>@i', '</p><p>', $objParagraph->html()));

                            if (in_array($objParagraph->getNode(0)->tagName, $arrTags))
                            {
                                $intTextAmount += strlen($objParagraph->text());
                            }

                            static::removeNodeIfNecessary($intPage, $intTextAmount, $intMaxAmount, $objParagraph);
                        }
                    );
                }
                else
                {
                    static::removeNodeIfNecessary($intPage, $intTextAmount, $intMaxAmount, $objElement);
                }
            }
        );

        $objTemplate->text = str_replace(['%7B', '%7D'], ['{', '}'], $objNode->saveHTML());

        // add pagination
        $objPagination               =
            new \Pagination(ceil($intTextAmount / $intMaxAmount), 1, \Config::get('maxPaginationLinks'), 'page_n' . $objModule->id);
        $objTemplate->newsPagination = $objPagination->generate("\n  ");
    }

    private static function removeNodeIfNecessary($intPage, $intTextAmount, $intMaxAmount, $objElement)
    {
        if ($intPage && is_numeric($intPage))
        {
            if (($intTextAmount < ($intPage - 1) * $intMaxAmount || $intTextAmount > $intPage * $intMaxAmount))
            {
                $objElement->remove();
            }
        }
        else
        {
            if ($intTextAmount > $intMaxAmount)
            {
                $objElement->remove();
            }
        }
    }
}