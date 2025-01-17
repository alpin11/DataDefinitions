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

namespace Wvision\Bundle\DataDefinitionsBundle\Model\Log;

use Exception;
use InvalidArgumentException;
use Pimcore\Model\Dao\AbstractDao;
use function count;
use function in_array;
use function is_bool;
use function is_callable;

class Dao extends AbstractDao
{
    protected string $tableName = 'data_definitions_import_log';

    /**
     * Get log by id
     *
     * @param null $id
     * @throws Exception
     */
    public function getById($id = null)
    {
        if ($id !== null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM '.$this->tableName.' WHERE id = ?', [$this->model->getId()]);

        if (!$data['id']) {
            throw new InvalidArgumentException(sprintf('Object with the ID %s does not exist', $this->model->getId()));
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save log
     *
     * @throws Exception
     */
    public function save()
    {
        $vars = $this->model->getObjectVars();

        $buffer = [];

        $validColumns = $this->getValidTableColumns($this->tableName);

        if (count($vars)) {
            foreach ($vars as $k => $v) {
                if (!in_array($k, $validColumns, true)) {
                    continue;
                }

                $getter = sprintf('get%s', ucfirst($k));

                if (!is_callable([$this->model, $getter])) {
                    continue;
                }

                $value = $this->model->$getter();

                if (is_bool($value)) {
                    $value = (int)$value;
                }

                $buffer[$k] = $value;
            }
        }

        if ($this->model->getId() !== null) {
            $this->db->update($this->tableName, $buffer, ['id' => $this->model->getId()]);

            return;
        }

        $this->db->insert($this->tableName, $buffer);
        $this->model->setId((int)$this->db->lastInsertId());
    }

    /**
     * Delete vote
     *
     * @throws Exception
     */
    public function delete()
    {
        $this->db->delete($this->tableName, ['id' => $this->model->getId()]);
    }
}
