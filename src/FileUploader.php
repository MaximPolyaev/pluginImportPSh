<?php


namespace MaximCode\ImportPalmira;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;


class FileUploader
{
    const _IMPORT_FILES_DIR_ = _PS_MODULE_DIR_ . 'importpalmira/importfiles/';

    private $fs;
    private $file_data;
    private $file_name;
    private $file_ext;
    private $file_tmp_name;
    private $file_size;
    private $file_path;
    private $errors = [];
    private $success = [];

    public function __construct()
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

    private function upload()
    {
        if ($this->isHistory()) {
            $this->file_path = self::_IMPORT_FILES_DIR_ . $this->file_name;
        } else if ($this->isExists() || !$this->extIsTrue()) {
            return;
        }

        if (empty($errors)) {
            $current_file = new File($this->file_tmp_name);
            $this->file_name = date("d-m-y-His-") . $this->file_name;
            $current_file->move(self::_IMPORT_FILES_DIR_, $this->file_name);

            if ($this->fs->exists(self::_IMPORT_FILES_DIR_ . $this->file_name)) {
                $this->file_path = self::_IMPORT_FILES_DIR_ . $this->file_name;
                $this->success[] = 'File has been successfully uploaded';
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
        if ($this->fs->exists(self::_IMPORT_FILES_DIR_ . $this->file_name) && $this->file_size) {
            $exist_file = new File(self::_IMPORT_FILES_DIR_ . $this->file_name);
            if ($exist_file->getSize() === $this->file_size) {
                $this->errors[] = 'Upload file error: file is exist';
                return true;
            }
        }

        return false;
    }

    private function extIsTrue()
    {
        if ($this->file_ext === 'csv' || $this->file_ext === 'xml') {
            return true;
        }

        $errors[] = "This file type ($this->file_ext) is not supported";
        return false;
    }

    private function getFileExt()
    {
        $file_ext = explode('.', $this->file_name);
        $file_ext = strtolower(end($file_ext));
        return $file_ext;
    }
}
