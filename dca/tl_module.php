<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_module'];

/**
 * Palettes
 */
$arrDca['palettes']['newsreader'] =
    str_replace('{template_legend', '{pagination_legend},addPagination;{template_legend', $arrDca['palettes']['newsreader']);

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
    ]
];

$arrDca['fields'] += $arrFields;