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
        if (!$objModule->addPagination)
        {
            return;
        }

        $intMaxAmount   = $objModule->paginationMaxCharCount;
        // add wrapper div since remove() called on root elements doesn't work (bug?)
        $objNode        = new HtmlPageCrawler('<div>' . $objTemplate->text . '</div>');
        $intTextAmount  = 0;
        $strCssSelector = $objModule->paginationCssSelector ?: '';
        $arrFilter      = array_map(
            function ($strTag) use ($strCssSelector)
            {
                return $strCssSelector . ' > ' . $strTag;
            },
            static::$arrTags
        );

        $intPage = Request::getGet('page_n' . $objModule->id);

        $objNode->filter('[class*="ce_"]')->each(
            function ($objElement) use (&$intTextAmount, $intMaxAmount, $intPage, $arrFilter, $objNode)
            {
                if (strpos($objElement->getAttribute('class'), 'ce_text') !== false)
                {
                    $objElement->filter(implode(',', $arrFilter))->each(function($objParagraph) use (&$intTextAmount, $intMaxAmount, $intPage) {
                        if ($intPage && is_numeric($intPage))
                        {
                            $intTextAmount += strlen($objParagraph->text());

                            if ($intTextAmount < ($intPage - 1) * $intMaxAmount || $intTextAmount > $intPage * $intMaxAmount)
                            {
                                $objParagraph->remove();
                            }
                        }
                        else
                        {
                            $intTextAmount += strlen($objParagraph->text());

                            if ($intTextAmount > $intMaxAmount)
                            {
                                $objParagraph->remove();
                            }
                        }
                    });
                }
                else
                {
                    if ($intPage && is_numeric($intPage))
                    {
                        if ($intTextAmount < ($intPage - 1) * $intMaxAmount || $intTextAmount > $intPage * $intMaxAmount)
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
        );

        $objTemplate->text = $objNode->saveHTML();

        // add pagination
        $objPagination               =
            new \Pagination(ceil($intTextAmount / $intMaxAmount), 1, \Config::get('maxPaginationLinks'), 'page_n' . $objModule->id);
        $objTemplate->newsPagination = $objPagination->generate("\n  ");
    }
}