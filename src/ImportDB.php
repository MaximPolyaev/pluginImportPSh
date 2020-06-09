<?php


namespace MaximCode\ImportPalmira;


use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Manufacturer;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\Shop;
use PrestaShop\PrestaShop\Adapter\Entity\SpecificPrice;
use PrestaShop\PrestaShop\Adapter\Entity\StockAvailable;
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
        foreach ($this->data as $data_item) {
            $is_update = $this->isUpdate($data_item);

            if (isset($data_item['shop'])) {
                $shop_id = Shop::getIdByName($data_item['shop']);
                $data_item['shop'] = (bool) $shop_id ? $shop_id : Shop::getShop((int) $data_item['shop'])['id_shop'];
                unset($shop_id);

                if ((bool) $data_item['shop']) {
                    Shop::setContext(Shop::CONTEXT_SHOP, (int) $data_item['shop']);
                    $this->shop_id = (int) $data_item['shop'];
                }
            }

            $product = new Product($data_item['id'] ?? null);
            $product->id_category_default = $this->default_category;
            $product->force_id = true;

            if (isset($data_item['id'])) {
                $product->id = $data_item['id'];
            }

            $this->addSimpleFields($product, $data_item);

            if (isset($data_item['supplier_reference'])) {
                $product->addSupplierReference('1', 0, $data_item['supplier_reference'] . 'new', '10', '1');
                $product->supplier_reference = $data_item['supplier_reference'];
                $product->id_supplier = 1;
            }

            if (isset($data_item['manufacturer'])) {
                $product->id_manufacturer = Manufacturer::getIdByName($data_item['manufacturer']) ?? 0;
            }

            if (isset($data_item['ean13'])) {
                if (\Validate::isEan13($data_item['ean13'])) {
                    $product->ean13 = $data_item['ean13'];
                }
                /*
                 * else output error
                 */
            }

            if (isset($data_item['upc'])) {
                if (\Validate::isUpc($data_item['upc'])) {
                    $product->upc = $data_item['upc'];
                }
                /*
                 * else output error
                 */
            }

            if (isset($data_item['isbn'])) {
                if (\Validate::isIsbn($data_item['isbn'])) {
                    $product->isbn = $data_item['isbn'];
                }
                /*
                 * else output error
                 */
            }

            if ($is_update) {
                $product->update();
                VarDumper::dump('update');
            } else {
                $product->add();
                VarDumper::dump('add');
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

            if (isset($data_item['quantity'])) {
                StockAvailable::setQuantity($product->id, 0, $data_item['quantity'], $this->shop_id, false);
            }

            if (isset($data_item['out_of_stock'])) {
                StockAvailable::setProductOutOfStock($product->id, $data_item['out_of_stock'], $this->shop_id);
            }

            if (isset($data_item['reduction_percent']) && $data_item['reduction_percent']) {
                $this->addSpecificPrice($product->id, $data_item);
            }

            if (isset($data_item['delete_existing_images'])) {
                if ($data_item['delete_existing_images'] === '1') {
                    $product->deleteImages();
                }
            }
        }

        \Tools::clearCache();
    }

    /**
     * @param $product Product
     * @param $info
     */
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

        if (isset($info['reference'])) {
            $product->reference = $info['reference'];
        }

        if (isset($info['ecotax'])) {
            $product->ecotax = $info['ecotax'];
        }

        if (isset($info['width'])) {
            $product->width = $info['width'];
        }

        if (isset($info['height'])) {
            $product->height = $info['height'];
        }

        if (isset($info['depth'])) {
            $product->depth = $info['depth'];
        }

        if (isset($info['weight'])) {
            $product->weight = $info['weight'];
        }

        if (isset($info['delivery_in_stock'])) {
            $product->delivery_in_stock = $info['delivery_in_stock'];
        }

        if (isset($info['delivery_out_stock'])) {
            $product->delivery_out_stock = $info['delivery_out_stock'];
        }

        if (isset($info['minimal_quantity'])) {
            $product->minimal_quantity = $info['minimal_quantity'];
        }

        if (isset($info['low_stock_alert'])) {
            $product->low_stock_alert = $info['low_stock_alert'];
        }

        if (isset($info['low_stock_threshold'])) {
            $product->low_stock_threshold = $info['low_stock_threshold'] ? $info['low_stock_threshold'] : null;
        }

        if (isset($info['visibility'])) {
            if (in_array($info['visibility'], ['both', 'catalog', 'search', 'none'])) {
                $product->visibility = $info['visibility'];
            }
        }

        if (isset($info['additional_shipping_cost'])) {
            $product->additional_shipping_cost = $info['additional_shipping_cost'];
        }

        if (isset($info['unity'])) {
            $product->unity = $info['unity'] ? $info['unity'] : null;
        }

        if (isset($info['unit_price'])) {
            $product->unit_price = $info['unit_price'] ? $info['unit_price'] : null;
        }

        if (isset($info['description_short'])) {
            $product->description_short = $info['description_short'];
        }

        if (isset($info['description'])) {
            $product->description = $info['description'];
        }

        if (isset($info['meta_title'])) {
            $product->meta_title = $info['meta_title'];
        }

        if (isset($info['meta_description'])) {
            $product->meta_description = $info['meta_description'];
        }

        if (isset($info['meta_keywords'])) {
            $product->meta_keywords = $info['meta_keywords'];
        }

        if (isset($info['link_rewrite'])) {
            $product->link_rewrite = $info['link_rewrite'];
        }

        if (isset($info['available_now'])) {
            $product->available_now = $info['available_now'];
        }

        if (isset($info['available_later'])) {
            $product->available_later = $info['available_later'];
        }

        if (isset($info['available_for_order'])) {
            $product->available_for_order = $info['available_for_order'];
        }

        if (isset($info['available_date'])) {
            if (\Validate::isDate($info['available_date'])) {
                $product->available_date = $info['available_date'];
            }
        }

        if (isset($info['date_add'])) {
            if (\Validate::isDate($info['date_add'])) {
                $product->date_add = $info['date_add'];
            }
        }

        if (isset($info['show_price'])) {
            $product->show_price = $info['show_price'];
        }

        if (isset($info['online_only'])) {
            $product->online_only = $info['online_only'];
        }

        if (isset($info['condition'])) {
            if (in_array($info['condition'], ['new', 'used', 'refurbished'])) {
                $product->condition = $info['condition'];
            }
        }

        if (isset($info['customizable'])) {
            $product->customizable = $info['customizable'];
        }

        if (isset($info['uploadable_files'])) {
            $product->uploadable_files = $info['uploadable_files'];
        }

        if (isset($info['text_fields'])) {
            $product->text_fields = $info['text_fields'];
        }

        if (isset($info['is_virtual'])) {
            $product->is_virtual = $info['is_virtual'];
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
        $products = Product::getProducts($this->language_id, 0, 0, 'id_product', 'ASC', false, false, $this->context);
        foreach ($products as $product) {
            $productObject = new Product($product['id_product']);
            $productObject->delete();
        }
    }
}
