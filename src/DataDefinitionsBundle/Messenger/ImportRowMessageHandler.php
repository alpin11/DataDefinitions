<?php
/**
 * Data Definitions.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2016-2019 w-vision AG (https://www.w-vision.ch)
 * @license    https://github.com/w-vision/DataDefinitions/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace Wvision\Bundle\DataDefinitionsBundle\Messenger;

use Wvision\Bundle\DataDefinitionsBundle\Importer\AsyncImporterInterface;
use Wvision\Bundle\DataDefinitionsBundle\Model\ImportDefinition;

class ImportRowMessageHandler
{
    public function __construct(
        private AsyncImporterInterface $importer,
    )
    {
    }

    public function __invoke(ImportRowMessage $message): void
    {
        $definition = ImportDefinition::getById($message->getDefinitionId());

        if (!$definition) {
            throw new \InvalidArgumentException('Invalid definition id');
        }
        
        $this->importer->doImportRowAsync(
            $definition,
            $message->getData(),
            $message->getParams(),
        );
    }
}