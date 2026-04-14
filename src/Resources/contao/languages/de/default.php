<?php

use Richardhj\ContaoAjaxReloadElementBundle\EventListener\AjaxReloadElementListener;

$GLOBALS['TL_LANG']['ERR']['ajaxReloadElement'][AjaxReloadElementListener::ERROR_ELEMENT_NOT_FOUND] = 'Das Element %s konnte nicht gefunden werden.';
$GLOBALS['TL_LANG']['ERR']['ajaxReloadElement'][AjaxReloadElementListener::ERROR_ELEMENT_AJAX_NOT_ALLOWED] = '%s mit der ID %u darf nicht per Ajax geladen werden.';
$GLOBALS['TL_LANG']['ERR']['ajaxReloadElement'][AjaxReloadElementListener::ERROR_ELEMENT_TYPE_UNKNOWN] = 'Es konnte nicht bestimmt werden, ob das Element ein Modul, Inhaltselement oder Artikel ist.';
