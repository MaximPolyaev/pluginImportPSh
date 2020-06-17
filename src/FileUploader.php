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
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;


class FileUploader
{
    const _IMPORT_FILES_DIR_ = _PS_MODULE_DIR_ . 'importpalmira/importfiles/';

    private $fs;
    private $module;
    private $file_data;
    private $file_name;
    private $file_ext;
    private $file_tmp_name;
    private $file_size;
    private $file_path;
    private $errors = [];
    private $success = [];

    public function __construct($module)
    {
        /**
         * If values have been submitted in the form, process.
         * @todo correct select import file from history
         */
        if ((bool)\Tools::isSubmit('submitImportpalmiraModule')) {
            if (isset($_FILES['IMPORTPALMIRA_FILE_IMPORT']) &&
                !empty($_FILES['IMPORTPALMIRA_FILE_IMPORT']) &&
                (bool)\Tools::getValue('IMPORTPALMIRA_FILE_IMPORT')
            ) {
                $this->fs = new Filesystem();
                $this->module = $module;
                $this->file_data = $_FILES['IMPORTPALMIRA_FILE_IMPORT'];
                $this->file_name = \Tools::getValue('IMPORTPALMIRA_FILE_IMPORT');
                $this->file_tmp_name = $this->file_data['tmp_name'];
                $this->file_ext = $this->getFileExt();
                $this->file_size = $this->file_data['size'];

                $this->upload();
            }
        }
    }

    public function getPath()
    {
        return $this->file_path ?? 'error';
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFileName() {
        return $this->file_name;
    }

    private function upload()
    {
        if ($this->isHistory()) {
            $this->file_path = self::_IMPORT_FILES_DIR_ . $this->file_name;
            return;
        } else if ($this->isExists() || !$this->extIsTrue()) {
            return;
        }

        if (empty($errors)) {
            $current_file = new File($this->file_tmp_name);
            $this->file_name = date("d-m-y-His-") . $this->file_name;
            $current_file->move(self::_IMPORT_FILES_DIR_, $this->file_name);

            if ($this->fs->exists(self::_IMPORT_FILES_DIR_ . $this->file_name)) {
                $this->file_path = self::_IMPORT_FILES_DIR_ . $this->file_name;
                $this->success[] = $this->module->getTranslator()->trans('File has been successfully uploaded', [], 'Modules.Importpalmira.Importpalmira');
            }
        }
    }

    private function isHistory()
    {
        return !$this->file_size &&
            $this->file_name &&
            $this->fs->exists(self::_IMPORT_FILES_DIR_ . $this->file_name);
    }

    private function isExists()
    {
        $files_name = self::getImportFilesName();

        foreach ($files_name as $name) {
            if ($this->file_name === $name) {
                $exist_file = new File(self::_IMPORT_FILES_DIR_ . $name);
                if ($exist_file->getSize() === $this->file_size) {
                    $this->errors[] = $this->module->getTranslator()->trans('Upload file error: file "%s" is exist', [$name], 'Modules.Importpalmira.Importpalmira');
                    return true;
                }
            }
        }

        $files_name = array_map(function ($name) {
            $regexp = '/^(\d{2}-\d{2}-\d{2}-\d{6}-)?(?<file_name>.+)$/ui';
            preg_match($regexp, $name, $matches);

            if (!isset($matches['file_name'])) {
                return null;
            }

            return ['file_name' => $matches['file_name'], 'original_name' => $name];
        }, $files_name);
        $files_name = array_filter($files_name, function ($name) {
            return !is_null($name);
        });

        foreach ($files_name as $name) {
            if ($this->file_name === $name['file_name']) {
                $exist_file = new File(self::_IMPORT_FILES_DIR_ . $name['original_name']);
                if ($exist_file->getSize() === $this->file_size) {
                    $this->errors[] = $this->module->getTranslator()->trans('Upload file error: file "%s" is exist', [$this->file_name], 'Modules.Importpalmira.Importpalmira');
                    return true;
                }
            }
        }

        return false;
    }

    private function extIsTrue()
    {
        if ($this->file_ext === 'csv' || $this->file_ext === 'xml') {
            return true;
        }
        $this->errors[] = $this->module->getTranslator()->trans('This file type (%s) is not supported', [$this->file_ext], 'Modules.Importpalmira.Importpalmira');
        return false;
    }

    private function getFileExt()
    {
        $file_ext = explode('.', $this->file_name);
        $file_ext = strtolower(end($file_ext));
        return $file_ext;
    }

    /**
     * Get array files name from "importfiles" folder
     * @return array
     */
    public static function getImportFilesName(): array
    {
        $import_dir = \ImportPalmira::_IMPORT_FILES_DIR_;
        $finder = new Finder();
        $finder->files()->in($import_dir);

        $files_names = [];

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $file_ext = $file->getExtension();

                if ($file_ext === 'csv' || $file_ext === 'xml') {
                    $files_names[] = $file->getFilename();
                }
            }
        }

        return $files_names;
    }
}
