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
        $arrTags = static::$arrTags;

        $intPage = Request::getGet('page_n' . $objModule->id);

        $objNode->filter('[class*="ce_"]')->each(
            function ($objElement) use (&$intTextAmount, $intMaxAmount, $intPage, $arrTags, $objNode, $strCssSelector)
            {
                if (strpos($objElement->getAttribute('class'), 'ce_text') !== false)
                {
                    $objElement->filter($strCssSelector . ' > *')->each(function($objParagraph) use (&$intTextAmount, $intMaxAmount, $intPage, $arrTags) {
                        if (in_array($objParagraph->getNode(0)->tagName, $arrTags))
                        {
                            if ($intPage && is_numeric($intPage))
                            {
                                $intTextAmount += strlen($objParagraph->text());
                            }
                            else
                            {
                                $intTextAmount += strlen($objParagraph->text());
                            }
                        }

                        static::removeNodeIfNecessary($intPage, $intTextAmount, $intMaxAmount, $objParagraph);
                    });
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