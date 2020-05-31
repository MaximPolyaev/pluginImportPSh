<?php


namespace MaximCode\ImportPalmira;


final class WebHelpers
{
    private function __construct()
    {
    }

    //Получить значение из массива $_REQUEST
    //Если значение отсутствует, вернуть $default_value
    static public function request($name, $default_value = null)
    {
        return isset($_REQUEST[$name]) && !is_array($_REQUEST[$name]) ? $_REQUEST[$name] : $default_value;
    }

    //Выдать ответ в формате JSON
    static public function echoJson(Array $value)
    {
//        header('Content-Type: application/json; charset=UTF-8');
        $ret = json_encode($value);
        if($ret !== false)
        {
            echo $ret;
            return true;
        }

        return false;
    }
}
