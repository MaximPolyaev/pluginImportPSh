<?php


namespace MaximCode\ImportPalmira;


use PrestaShop\PrestaShop\Adapter\Entity\Context;
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\DbQuery;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use Symfony\Component\VarDumper\VarDumper;

class ImportHelper
{
    public static function optimize_matching($data, $keys)
    {
        $data = array_map(function ($data_item) use ($keys) {
            $new_data_item = array_combine($keys, $data_item);
            $new_data_item = array_filter($new_data_item, function ($key) {
                return $key !== 'no';
            }, ARRAY_FILTER_USE_KEY);

            return $new_data_item;
        }, $data);


        return $data;
    }

    public static function addUnsavedFile($file_path)
    {
        $json_path = _PS_MODULE_DIR_ . 'importpalmira/json/unsavedfiles.json';
        $json_content = json_decode(file_get_contents($json_path), true);
        $json_content[] = $file_path;
        file_put_contents($json_path, json_encode($json_content));
    }

    public static function cleanUnsavedFiles()
    {
        $json_path = _PS_MODULE_DIR_ . 'importpalmira/json/unsavedfiles.json';
        $json_content = json_decode(file_get_contents($json_path), true);

        foreach ($json_content as $path) {
            \Tools::deleteFile($path);
        }

        file_put_contents($json_path, json_encode([]));
    }

    /**
     * @param $unique_field string
     * @param $check_field string
     * @param $context Context
     * @return bool
     */
    public static function isExistProductByField($unique_field, $check_field, $context = null)
    {
        switch($unique_field) {
            case 'id':
                return (bool)Product::getProductName((int)$check_field);
            case 'name':
                return (bool)Product::searchByName($context->language->id, $check_field);
            case 'reference':
                return (bool)Product::getIdByReference($check_field);
            case 'ean13':
                return (bool)Product::getIdByEan13($check_field);
            case 'upc':
                return (bool)self::getProductIdByUpc($check_field);
            case 'isbn':
                return (bool)self::getProductIdByIsbn($check_field);
            default:
                return false;
        }
    }

    /**
     * @param $unique_field
     * @param $check_field
     * @param $context
     * @return array|bool|false|int|string|null
     */
    public static function getProductIdByField($unique_field, $check_field, $context)
    {
        switch($unique_field) {
            case 'name':
                return Product::searchByName($context->language->id, $check_field)[0]['id_product'];
            case 'reference':
                return Product::getIdByReference($check_field);
            case 'ean13':
                return Product::getIdByEan13($check_field);
            case 'upc':
                return self::getProductIdByUpc($check_field);
            case 'isbn':
                return self::getProductIdByIsbn($check_field);
            default:
                return 0;
        }
    }

    public static function getProductIdByUpc($upc) {
        if (empty($upc)) {
            return 0;
        }

        if (!\Validate::isUpc($upc)) {
            return 0;
        }

        $query = new DbQuery();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.upc = \'' . pSQL($upc) . '\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    public static function getProductIdByIsbn($isbn) {
        if (empty($isbn)) {
            return 0;
        }

        if (!\Validate::isIsbn($isbn)) {
            return 0;
        }

        $query = new DbQuery();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.isbn = \'' . pSQL($isbn) . '\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }
}
