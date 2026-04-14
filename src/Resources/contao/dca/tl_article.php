<?php

$GLOBALS['TL_DCA']['tl_article']['fields']['allowAjaxReload'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_article']['allowAjaxReload'],
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr w50',
    ],
    'sql' => "char(1) NOT NULL default ''",
];
