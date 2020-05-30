<?php
//session_start();
//print_r($_SESSION);


use Symfony\Component\VarDumper\VarDumper;

require_once('../../config/config.inc.php');
require_once('../../init.php');

if(!isset($_SESSION))
{
    session_start();
}

VarDumper::dump('test');
VarDumper::dump(Tools::getAllValues());
VarDumper::dump($_SESSION['IMPORT_PALMIRA'] ?? null);
