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


class FileReader
{
    private $file_ext;
    private $reader;

    public function __construct($file_path)
    {
        $this->file_ext = $this->getFileExt($file_path);

        switch($this->file_ext) {
            case 'xml':
                $this->reader = new XmlFileReader($file_path);
                break;
            case 'csv':
                $this->reader = new CsvFileReader($file_path);
                break;
            default:
                throw new \Exception("Invalid file extension $this->file_ext");
        }

        return $this;
    }

    public function init()
    {
        $this->reader->init();

        return $this;
    }

    public function getErrors()
    {
        return $this->reader->getErrors();
    }

    public function getHeaders()
    {
        return $this->reader->getHeaders();
    }

    public function getData($from = 0, $limit = 0, $num_skip_rows = 1)
    {
        return $this->reader->getData($from, $limit, $num_skip_rows);
    }

    private function getFileExt($file_path)
    {
        $file_ext = explode('.', $file_path);
        $file_ext = strtolower(end($file_ext));
        return $file_ext;
    }
}
