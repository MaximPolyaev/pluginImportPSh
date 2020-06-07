<?php


namespace MaximCode\ImportPalmira;


use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\SpecificPrice;
use PrestaShop\PrestaShop\Adapter\Import\Handler\ProductImportHandler;
use Symfony\Component\VarDumper\VarDumper;

class ImportDB
{
    private $data;
    private $data_length;
    private $context;
    private $module;
    private $shop_id;
    private $currency_id;
    private $country_id;
    private $language_id;
    private $default_lang;
    private $default_category;
    private $products;
    private $errors = [];

    public function __construct($module, $import_data)
    {
        $this->data = $import_data;
        $this->data_length = count($import_data);
        $this->module = $module;
        $this->context = $this->module->getContext();
        $this->language_id = $this->context->language->id;
        $this->products = Product::getProducts($this->language_id, 0, 0, 'id_product', 'ASC');
        $this->default_lang = \Configuration::get('PS_LANG_DEFAULT') ?? 1;
        $this->default_category = \Configuration::get('PS_HOME_CATEGORY') ?? 1;
        $this->shop_id = $this->context->shop->id;
        $this->currency_id = $this->context->currency->id;
        $this->country_id = $this->context->country->id;

        $this->import();
    }

    private function import()
    {
//        VarDumper::dump($this->data);
        foreach ($this->data as $data_item) {
            $is_update = $this->isUpdate($data_item);

            $product = new Product($data_item['id'] ?? null);
            $product->id_category_default = $this->default_category;
            $product->force_id = true;

            if (isset($data_item['id'])) {
                $product->id = $data_item['id'];
            }

            $this->addUniqueFields($product, $data_item);
            $this->addSimpleFields($product, $data_item);

            if ($is_update) {
                $product->update();
            } else {
                $product->add();
            }

            if (isset($data_item['category'])) {
                $new_categories = [$this->default_category];
                if ($enumeration_str = self::getEnumerationString($data_item['category'])) {
                    $enumeration_arr = self::convertEnumerationStrToArray($enumeration_str);
                    $enumeration_arr = array_filter(
                        array_map(function ($item) {
                            return Category::searchByName(
                                    $this->language_id,
                                    $item,
                                    true)['id_category'] ?? false;
                        }, $enumeration_arr),
                        function ($item) {
                            return $item;
                        });

                    $new_categories = array_unique(array_merge($new_categories, $enumeration_arr));

                } else if ($category = Category::searchByName($this->language_id, $data_item['category'], true)) {
                    $new_categories[] = $category['id_category'];
                    $new_categories = array_unique($new_categories);
                }
                $product->updateCategories($new_categories);
            } else if (!$is_update) {
                $new_categories = [$this->default_category];
                $product->updateCategories($new_categories);
            }

//            VarDumper::dump($product->id);

            if (isset($data_item['reduction_percent']) && $data_item['reduction_percent']) {
                $this->addSpecificPrice($product->id, $data_item);
            }
        }

//        \Tools::clearCache();
    }

    public function addUniqueFields($product, $info)
    {
        if (isset($info['reference'])) {
            $product->reference = $info['reference'];
        }
    }

    public function addSimpleFields($product, $info)
    {
        if (isset($info['active'])) {
            $product->active = $info['active'];
        }

        if (isset($info['name'])) {
            $product->name = $info['name'];
        }

        if (isset($info['price_tex'])) {
            $product->price = $info['price_tex'];
        }

        if (isset($info['id_tax_rules_group'])) {
            $product->id_tax_rules_group = $info['id_tax_rules_group'];
        }

        if (isset($info['wholesale_price'])) {
            $product->wholesale_price = $info['wholesale_price'];
        }

        if (isset($info['on_sale'])) {
            $product->on_sale = $info['on_sale'];
        }
    }

    public function addSpecificPrice($product_id, $info)
    {
        try {
            $id_shop = $this->shop_id;
            $specific_price = SpecificPrice::getSpecificPrice((int)$product_id, $id_shop, 1, 0, 0, 1, 0, 0, 0, 0);

            if (is_array($specific_price) && isset($specific_price['id_specific_price'])) {
                $specific_price = new SpecificPrice((int)$specific_price['id_specific_price']);
            } else {
                $specific_price = new SpecificPrice();
            }

            $specific_price->id_product = (int)$product_id;
            $specific_price->id_specific_price_rule = 0;
            $specific_price->id_shop = $id_shop;
            $specific_price->id_currency = 0;
            $specific_price->id_country = 0;
            $specific_price->id_group = 0;
            $specific_price->price = (isset($info['price']) && $info['price']) ? $info['price'] : -1;
            $specific_price->id_customer = 0;

            $specific_price->from_quantity = 1;

//            if ($specific_price->price > 1) {
//                $specific_price->reduction = 0;
//                $specific_price->reduction_type ='amount';
//            } else {
//                $specific_price->reduction = (isset($info['reduction_price']) && $info['reduction_price']) ? (float)str_replace(',', '.', $info['reduction_price']) : $info['reduction_percent'] / 100;
//                $specific_price->reduction_type = 'percentage';
//            }
            $specific_price->reduction_tax = 1;

            $specific_price->reduction = $info['reduction_percent'];
            $specific_price->reduction_type = 'amount';

            $specific_price->from = '0000-00-00 00:00:00';
//            $specific_price->from = (isset($info['reduction_from']) && \Validate::isDate($info['reduction_from'])) ? $info['reduction_from'] : '0000-00-00 00:00:00';
            $specific_price->to = '0000-00-00 00:00:00';
//            $specific_price->to = (isset($info['reduction_to']) && \Validate::isDate($info['reduction_to'])) ? $info['reduction_to'] : '0000-00-00 00:00:00';

            if (!$specific_price->save()) {
                return \Tools::displayError('An error occurred while updating the specific price.');
            }
        } catch (\Exception $e) {
            return \Tools::displayError('An error occurred while updating the specific price. ' . $e);
        }
    }

    public function isUpdate($data_item)
    {
        $is_update = false;
        if (isset($data_item['id'])) {
            foreach ($this->products as $product) {
                if ($product['id_product'] === $data_item['id']) {
                    $is_update = true;
                    break;
                }
            }
        }

        return $is_update;
    }

    public static function getEnumerationString($str)
    {
        $regexp = '/^\((?<str>.+)\)$/ui';
        preg_match($regexp, $str, $result);

        return $result['str'] ?? false;
    }

    public static function convertEnumerationStrToArray($str)
    {
        $arr = array_map(function ($item) {
            return trim($item);
        }, explode(',', $str));

        return $arr;
    }

    public function deleteAllProducts()
    {
        $products = Product::getProducts($this->language_id, 0, 0, 'id_product', 'ASC');
        foreach ($products as $product) {
            $productObject = new Product($product['id_product']);
            $productObject->delete();
        }
    }
}