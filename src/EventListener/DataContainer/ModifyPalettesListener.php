<?php

declare(strict_types=1);

namespace Richardhj\ContaoAjaxReloadElementBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

#[AsCallback(table: 'tl_article', target: 'config.onload')]
#[AsCallback(table: 'tl_content', target: 'config.onload')]
#[AsCallback(table: 'tl_module', target: 'config.onload')]
class ModifyPalettesListener
{
    public function __invoke(DataContainer $dataContainer): void
    {
        if (
            !isset($GLOBALS['TL_DCA'][$dataContainer->table]['palettes'])
            || !\is_array($GLOBALS['TL_DCA'][$dataContainer->table]['palettes'])
        ) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$dataContainer->table]['palettes'] as $name => $palette) {
            if (!\is_string($palette)) {
                continue;
            }

            if ('tl_content' === $dataContainer->table && 'module' === $name) {
                continue;
            }

            PaletteManipulator::create()
                ->addField('allowAjaxReload', 'expert_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette($name, $dataContainer->table)
            ;
        }
    }
}
