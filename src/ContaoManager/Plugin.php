<?php

/**
 * This file is part of dvc/contao-ajax_reload_element.
 *
 * Copyright (c) 2016-2017 Richard Henkenjohann
 *
 * @package   dvc/contao-ajax_reload_element
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2016-2017 Richard Henkenjohann
 * @license   https://github.com/dvc/contao-ajax_reload_element/blob/master/LICENSE LGPL-3.0
 */

namespace Dvc\ContaoAjaxReloadElementBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Dvc\ContaoAjaxReloadElementBundle\DvcContaoAjaxReloadElementBundle;

/**
 * Contao Manager plugin.
 */
class Plugin implements BundlePluginInterface
{

    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(DvcContaoAjaxReloadElementBundle::class)
                ->setLoadAfter(
                    [
                        ContaoCoreBundle::class
                    ]
                )
                ->setReplace(['zz_ajax_reload_element'])
        ];
    }
}
