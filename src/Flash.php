<?php


namespace MaximCode\ImportPalmira;


use Symfony\Component\VarDumper\VarDumper;

class Flash
{
    private static $instances = [];

    public function __construct()
    {
    }

    /**
     * Одиночки не должны быть клонируемыми.
     */
    protected function __clone()
    {
    }

    /**
     * Одиночки не должны быть восстанавливаемыми из строк.
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): Flash
    {
        if(!isset($_SESSION))
        {
            session_start();
        }

        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static;
        }

        return self::$instances[$cls];
    }

    public function add($key, $value) {
        $_SESSION['IMPORT_PALMIRA']['FLASH'][$key] = $value;
    }

    public function get($key) {
        $value = $_SESSION['IMPORT_PALMIRA']['FLASH'][$key];
        unset($_SESSION['IMPORT_PALMIRA']['FLASH'][$key]);
        return $value;
    }

    public function destroy() {
        unset($_SESSION['IMPORT_PALMIRA']['FLASH']);
    }
}
