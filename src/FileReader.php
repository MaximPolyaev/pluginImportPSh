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
    private $data_from;
    private $data_to;

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

            if (empty($this->headers) || is_null($this->headers)) {
                $this->errors[] = 'Error read headers';
                return false;
            }
        }

        return $this->headers;
    }

    public function getData($from = 0, $to = 0)
    {
        if($to > 0 && $from > $to) {
            return null;
        }

        $this->data_from = $from;
        $this->data_to = $to;
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
        if (!$this->getHeaders()) {
            return;
        }

        $counter = 0;

        while (($row = fgetcsv($this->file_open, $this->str_length, $this->delimiter)) !== false) {
            $row_new = [];
            foreach($this->headers as $i => $header_name) {
                $row_new[$header_name] = $row[$i];
            }

            if ($this->data_from === 0 && $this->data_to === 0) {
                $this->data[] = $row_new;
            } else if ($this->data_from === 0 && $this->data_to > 0) {
                if($counter <= $this->data_to) {
                    $this->data[] = $row_new;
                } else {
                    return;
                }
            } else if ($this->data_from > 0 && $this->data_to == 0) {
                if($counter >= $this->data_from) {
                    $this->data[] = $row_new;
                } else {
                    $counter++;
                    continue;
                }
            } else if ($this->data_from > 0 && $this->data_to > 0) {
                if($counter >= $this->data_from) {
                    if($counter <= $this->data_to) {
                        $this->data[] = $row_new;
                    } else {
                        return;
                    }
                } else {
                    $counter++;
                    continue;
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
