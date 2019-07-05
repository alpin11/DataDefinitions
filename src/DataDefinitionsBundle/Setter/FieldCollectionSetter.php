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

namespace Wvision\Bundle\DataDefinitionsBundle\Setter;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as AbstractFieldCollection;
use Wvision\Bundle\DataDefinitionsBundle\Getter\GetterInterface;
use Wvision\Bundle\DataDefinitionsBundle\Model\ExportMapping;
use Wvision\Bundle\DataDefinitionsBundle\Model\ImportMapping;
use Wvision\Bundle\DataDefinitionsBundle\Model\MappingInterface;

class FieldCollectionSetter implements SetterInterface, GetterInterface
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function set(Concrete $object, $value, ImportMapping $map, $data)
    {
        $keyParts = explode('~', $map->getToColumn());

        $config = $map->getSetterConfig();
        $keys = $config['fieldcollectionKeys'];
        $fieldName = $config['fieldcollectionField'];
        $class = $config['class'];
        $keys = explode(',', $keys);
        $fieldCollectionClass = 'Pimcore\Model\DataObject\Fieldcollection\Data\\'.ucfirst($class);
        $field = $keyParts[3];
        $mappedKeys = [];

        foreach ($keys as $key) {
            $tmp = explode(':', $key);

            $mappedKeys[] = [
                'from' => $tmp[0],
                'to' => $tmp[1],
            ];
        }

        $getter = sprintf('get%s', ucfirst($fieldName));
        $setter = sprintf('set%s', ucfirst($fieldName));

        if (method_exists($object, $getter)) {
            $fieldCollection = $object->$getter();

            if (!$fieldCollection instanceof \Pimcore\Model\DataObject\Fieldcollection) {
                $fieldCollection = new \Pimcore\Model\DataObject\Fieldcollection();
            }

            $items = $fieldCollection->getItems();
            $found = false;

            foreach ($items as $item) {
                if (is_a($item, $fieldCollectionClass) && $this->isValidKey($mappedKeys, $item, $data)) {
                    if ($item instanceof AbstractFieldCollection) {
                        $item->setValue($field, $value);
                    }

                    $found = true;
                }
            }

            if (!$found) {
                // Create new entry
                $item = new $fieldCollectionClass();

                if ($item instanceof AbstractFieldCollection) {
                    foreach ($mappedKeys as $key) {
                        $item->setValue($key['to'], $data[$key['from']]);
                    }

                    $item->setValue($field, $value);

                    $fieldCollection->add($item);
                }
            }

            $object->$setter($fieldCollection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(Concrete $object, ExportMapping $map, $data)
    {
        $keyParts = explode('~', $map->getFromColumn());

        $config = $map->getGetterConfig();
        $fieldName = $config['field'];
        $class = $config['class'];
        $fieldCollectionClass = 'Pimcore\Model\DataObject\Fieldcollection\Data\\'.ucfirst($class);
        $field = $keyParts[3];

        $getter = sprintf('get%s', ucfirst($fieldName));

        if (method_exists($object, $getter)) {
            $fieldCollection = $object->$getter();

            if (!$fieldCollection instanceof \Pimcore\Model\DataObject\Fieldcollection) {
                return null;
            }

            $items = $fieldCollection->getItems();
            $values = [];

            foreach ($items as $item) {
                if (!$item instanceof AbstractFieldCollection) {
                    continue;
                }

                if (!is_a($item, $fieldCollectionClass)) {
                    continue;
                }

                $getter = sprintf('get%s', ucfirst($field));

                if (method_exists($item, $getter)) {
                    $values[$item->getIndex()] = $item->$getter();
                }
            }

            return $values;
        }

        return null;
    }

    /**
     * @param array $keys
     * @param       $fieldcollection
     * @param       $data
     * @return boolean
     * @throws \Exception
     */
    protected function isValidKey(array $keys, AbstractFieldCollection $fieldcollection, $data)
    {
        foreach ($keys as $key) {
            $getter = sprintf('get%s', ucfirst($key['to']));

            if (method_exists($fieldcollection, $getter)) {
                $keyValue = $fieldcollection->$getter();

                if ($keyValue !== $data[$key['from']]) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }
}


