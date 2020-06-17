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


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;


class CsvFileReader
{
    private $file_path;
    private $fs;
    private $file;
    private $file_open;
    private $headers;
    private $delimiter;
    private $data;
    private $errors = [];
    private $str_length;
    private $data_from;
    private $data_limit;
    private $num_skip_rows;

    public function __construct($file_path)
    {
        $this->file_path = $file_path;
        $this->fs = new Filesystem();

        return $this;
    }

    public function init()
    {
        $this->delimiter = ';';
        $this->str_length = 2000;

        if (!$this->isExists($this->file_path)) {
            $this->errors[] = 'The file does not exist';
            return $this;
        }

        $this->file = new File($this->file_path);
        if ($this->file->getSize() == 0) {
            $this->errors[] = 'This file is empty';
            return $this;
        }

        $this->file_path = $this->file->getRealPath();
        $this->file_open = fopen($this->file_path, 'r');

        if (!$this->file_open) {
            $this->errors[] = 'Error open file';
            return $this;
        }

        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getHeaders()
    {
        if (empty($this->headers) || is_null($this->headers)) {
            $this->readHeaders();

            if (empty($this->headers) || is_null($this->headers)) {
                $this->errors[] = 'Error read headers';
                return false;
            }
        }

        return $this->headers;
    }

    public function getData($from = 0, $limit = 0, $num_skip_rows = 1)
    {
        if ($from < 0 || $limit < 0) {
            return null;
        }

        $this->data_from = $from;
        $this->data_limit = $limit;
        $this->num_skip_rows = $num_skip_rows;

        $this->readData();

        if (empty($this->data) || is_null($this->data)) {
            return null;
        }

        return $this->data;
    }

    private function readHeaders()
    {
        $this->headers = fgetcsv($this->file_open, $this->str_length, $this->delimiter);
    }

    private function readData()
    {
        $counter = 0;

        $this->data_from += (int)$this->num_skip_rows;

        if (!empty($this->headers)) {
            if ($this->data_from === 0) {
                $this->data[] = $this->headers;
                if ($this->data_limit > 0) {
                    $this->data_limit--;
                }
            } else if ($this->data_from > 0) {
                $this->data_from--;
            }
        }

        $to = $this->data_from + $this->data_limit;

        while (($row = fgetcsv($this->file_open, $this->str_length, $this->delimiter)) !== false) {
            if ($this->data_from === 0 && $to === 0) {
                $this->data[] = $row;
                continue;
            } else if ($this->data_from === 0 && $this->data_limit > 0) {
                if ($counter < $to) {
                    $this->data[] = $row;
                } else {
                    return;
                }
            } else if ($this->data_from > 0 && $this->data_limit === 0 && $counter >= $this->data_from) {
                $this->data[] = $row;
            } else if ($this->data_from > 0 && $this->data_limit > 0 && $counter >= $this->data_from) {
                if ($counter < $to) {
                    $this->data[] = $row;
                } else {
                    return;
                }
            }

            $counter++;
        }
    }

    private function isExists($file_path)
    {
        if ($file_path === null || $file_path === '') {
            return false;
        }

        return $this->fs->exists($file_path);
    }

    public function __destruct()
    {
        if ($this->file_open) {
            fclose($this->file_open);
        }
    }
}
