<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace MaximCode\ImportPalmira;


class JsonCfg
{
    private $fileContent;
    private $save_status;
    private $delete_status;
    private $save_errors = [];
    private $delete_errors = [];
    private $use_errors = [];
    const _JSON_PATH_ = _PS_MODULE_DIR_ . 'importpalmira/json/cfg.json';

    public function __construct()
    {
        $this->fileContent = json_decode(file_get_contents(self::_JSON_PATH_), true);
    }

    public function save($name, $data)
    {
        if (isset($this->fileContent[$name])) {
            $this->save_errors[] = "Имя конфигурации существует, выберите другое имя";
            $this->save_status = false;
            return;
        }

        $this->fileContent[$name] = $data;
        $this->save_status = true;

        file_put_contents(self::_JSON_PATH_, json_encode($this->fileContent));
    }

    public function delete($name)
    {
        if (!isset($this->fileContent[$name])) {
            $this->delete_errors[] = "Невозможно удалить конфигурацию \"${name}\", ее не существует";
            $this->delete_status = false;
            return;
        }

        unset($this->fileContent[$name]);
        $this->delete_status = true;

        file_put_contents(self::_JSON_PATH_, json_encode($this->fileContent));
    }

    public function getData($name)
    {
        if (!isset($this->fileContent[$name])) {
            $this->use_errors[] = 'Конфигурация не определена';
            return null;
        }

        if (empty($this->fileContent[$name])) {
            $this->use_errors[] = "В конфигурации \"${name}\" отсутствуют данные";
            return null;
        }

        return $this->fileContent[$name];
    }

    public function getSaveErrors()
    {
        return $this->save_errors;
    }

    public function getSaveStatus() {
        return $this->save_status;
    }

    public function getDeleteErrors()
    {
        return $this->delete_errors;
    }

    public function getDeleteStatus()
    {
        return $this->delete_status;
    }

    public function getUseErrors()
    {
        return $this->use_errors;
    }

    public function getNames() {
        return array_keys($this->fileContent) ?? null;
    }
}
