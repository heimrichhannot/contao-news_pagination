<?php

namespace HeimrichHannot\NewsPagination;


use HeimrichHannot\Request\Request;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class Hooks extends \Controller
{
    public function addNewsPagination($objTemplate, $arrArticle, $objModule)
    {
        if (!$objModule->addPagination)
        {
            return;
        }

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
}