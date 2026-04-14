<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'allowAjaxReload';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['allowAjaxReload'] = 'ajaxReloadFormSubmit';

$GLOBALS['TL_DCA']['tl_module']['fields']['allowAjaxReload'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['allowAjaxReload'],
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'tl_class' => 'clr w50',
    ],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ajaxReloadFormSubmit'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['ajaxReloadFormSubmit'],
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr w50',
    ],
    'sql' => "char(1) NOT NULL default ''",
];
