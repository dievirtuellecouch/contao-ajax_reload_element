<?php

declare(strict_types=1);

namespace Richardhj\ContaoAjaxReloadElementBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Richardhj\ContaoAjaxReloadElementBundle\RichardhjContaoAjaxReloadElementBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(RichardhjContaoAjaxReloadElementBundle::class)
                ->setLoadAfter(
                    [
                        ContaoCoreBundle::class,
                    ]
                )
                ->setReplace(['zz_ajax_reload_element']),
        ];
    }
}
