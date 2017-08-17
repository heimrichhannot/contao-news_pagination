<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_module'];

/**
 * Palettes
 */
$arrDca['palettes']['__selector__'][] = 'addPagination';
$arrDca['palettes']['newsreader'] =
    str_replace('{template_legend', '{pagination_legend},addPagination;{template_legend', $arrDca['palettes']['newsreader']);

/**
 * Subpalettes
 */
$arrDca['subpalettes']['addPagination'] = 'paginationMaxCharCount,paginationParentCssSelector,paginationCeTextCssSelector';

/**
 * Fields
 */
$arrFields = [
    'addPagination' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_module']['addPagination'],
        'exclude'                 => true,
        'inputType'               => 'checkbox',
        'eval'                    => ['tl_class' => 'w50', 'submitOnChange' => true],
        'sql'                     => "char(1) NOT NULL default ''"
    ],
    'paginationMaxCharCount' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_module']['paginationMaxCharCount'],
        'exclude'                 => true,
        'inputType'               => 'text',
        'eval'                    => ['rgxp' => 'digit', 'tl_class' => 'w50 clr', 'mandatory' => true],
        'sql'                     => "int(10) unsigned NOT NULL default '0'"
    ],
    'paginationCeTextCssSelector' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_module']['paginationCeTextCssSelector'],
        'exclude'                 => true,
        'inputType'               => 'text',
        'eval'                    => ['tl_class' => 'w50', 'decodeEntities' => true],
        'sql'                     => "varchar(128) NOT NULL default ''"
    ]
];

$arrDca['fields'] += $arrFields;