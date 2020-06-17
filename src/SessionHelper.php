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


final class SessionHelper
{
    //Открыта ли сессия
    private static $started = false;

    private function __construct()
    {
    }

    //Безопасное открытие сессии
    static private function safeSessionStart()
    {
        $name = session_name();
        $cookie_session = false;
        if(ini_get('session.use_cookies') && isset($_COOKIE[$name]))
        {
            $cookie_session = true;
            $sessid = $_COOKIE[$name];
        }
        else if(!ini_get('session.use_only_cookies') && isset($_GET[$name]))
        {
            $sessid = $_GET[$name];
        }
        else
        {
            return @session_start();
        }

        if(is_array($sessid) || !preg_match('/^[a-zA-Z0-9,-]+$/', $sessid))
        {
            if($cookie_session) //Try to reset incorrect session cookie
            {
                setcookie($name, '', 1);
                unset($_COOKIE[$name]);
                if(!ini_get('session.use_only_cookies') && isset($_GET[$name]))
                    unset($_GET[$name]);

                return @session_start();
            }

            return false;
        }

        return @session_start();
    }

    //Открыть сессию
    static public function init()
    {
        if(!self::$started)
        {
            if(self::safeSessionStart()) {
                self::$started = true;
            }
        }
    }

    //Открыта ли сессия
    static public function isStarted()
    {
        return self::$started;
    }

    //Завершить сессию
    static public function close()
    {
        if(self::$started)
        {
            session_write_close();
            self::$started = false;
        }
    }

    //Получить значение ключа с именем $name из сессии
    //Если ключ отсутствует, будет возвращено значение $default_value
    static public function get($name, $default_value = null)
    {
        return isset($_SESSION['IMPORT_PALMIRA']['PGS'][$name])
            ? $_SESSION['IMPORT_PALMIRA']['PGS'][$name] : $default_value;
    }

    //Установить значение ключа с именем $name в $value
    static public function set($name, $value)
    {
        $_SESSION['IMPORT_PALMIRA']['PGS'][$name] = $value;
    }

    //Удалить ключ с именем $name из сессии
    static public function remove($name)
    {
        unset($_SESSION['IMPORT_PALMIRA']['PGS'][$name]);
    }
}
