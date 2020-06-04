<?php


namespace MaximCode\ImportPalmira;


use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\VarDumper\VarDumper;

class JsonCfg
{
    private $fileContent;
    private $status_save;
    private $errors = [];
    const _JSON_PATH_ = _PS_MODULE_DIR_ . 'importpalmira/json/cfg.json';

    public function __construct()
    {
        $this->fileContent = json_decode(file_get_contents(self::_JSON_PATH_), true);
    }

    public function save($name, $data)
    {
        if (isset($this->fileContent[$name])) {
            $this->errors[] = "Имя конфигурации существует, выбирите другое имя";
            $this->status_save = false;
            return;
        }

        $this->fileContent[$name] = $data;
        $this->status_save = true;

        file_put_contents(self::_JSON_PATH_, json_encode($this->fileContent));
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getStatusSave() {
        return $this->status_save;
    }
}
