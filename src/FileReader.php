<?php


namespace MaximCode\ImportPalmira;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\VarDumper\VarDumper;

class FileReader
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

    public function __construct($file_path)
    {
        $this->file_path = $file_path;
        $this->fs = new Filesystem();

        return $this;
    }

    public function init($delimiter = ";", $str_length = 2000)
    {
        $this->delimiter = $delimiter;
        $this->str_length = $str_length;

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

            if(empty($this->headers) || is_null($this->headers)) {
                $this->errors[] = 'Error read headers';
                return false;
            }
        }

        return $this->headers;
    }

    public function getData()
    {
        return $this->data;
    }

    private function readHeaders()
    {
        $this->headers = fgetcsv($this->file_open, $this->str_length, $this->delimiter);
//        VarDumper::dump($this->headers);
    }

    private function readData()
    {

    }

    private function isExists($file_path)
    {
        if ($file_path === null || $file_path === '') {
            return false;
        }

        return $this->fs->exists($file_path);
    }
}
