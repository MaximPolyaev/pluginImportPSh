<?php


namespace MaximCode\ImportPalmira;


use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Context;
use PrestaShop\PrestaShop\Adapter\Entity\Feature;
use PrestaShop\PrestaShop\Adapter\Entity\FeatureValue;
use PrestaShop\PrestaShop\Adapter\Entity\Image;
use PrestaShop\PrestaShop\Adapter\Entity\ImageManager;
use PrestaShop\PrestaShop\Adapter\Entity\ImageType;
use PrestaShop\PrestaShop\Adapter\Entity\Language;
use PrestaShop\PrestaShop\Adapter\Entity\Manufacturer;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\ProductDownload;
use PrestaShop\PrestaShop\Adapter\Entity\Shop;
use PrestaShop\PrestaShop\Adapter\Entity\SpecificPrice;
use PrestaShop\PrestaShop\Adapter\Entity\StockAvailable;
use PrestaShop\PrestaShop\Adapter\Entity\Tag;
use Symfony\Component\VarDumper\VarDumper;

class ImportDB
{
    private $data;
    private $data_length;
    /**
     * @var Context
     */
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
        $this->products = $this->getProducts();
        $this->default_lang = \Configuration::get('PS_LANG_DEFAULT') ?? 1;
        $this->default_category = \Configuration::get('PS_HOME_CATEGORY') ?? 1;
        $this->shop_id = $this->context->shop->id;
        $this->currency_id = $this->context->currency->id;
        $this->country_id = $this->context->country->id;

//        $this->deleteAllProducts();
        $this->import();
    }

    private function import()
    {
        foreach ($this->data as $data_item) {
            $is_update = $this->isUpdate($data_item);

            if (isset($data_item['shop'])) {
                $shop_id = Shop::getIdByName($data_item['shop']);
                $data_item['shop'] = (bool)$shop_id ? $shop_id : Shop::getShop((int)$data_item['shop'])['id_shop'];
                unset($shop_id);

                if ((bool)$data_item['shop']) {
                    Shop::setContext(Shop::CONTEXT_SHOP, (int)$data_item['shop']);
                    $this->shop_id = (int)$data_item['shop'];
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

            if (isset($data_item['nb_downloadable']) ||
                isset($data_item['nb_days_accessible']) ||
                isset($data_item['file_url']) ||
                (
                    isset($data_item['date_expiration']) &&
                    \Validate::isDate($data_item['date_expiration'])
                )) {
                if ($product->is_virtual) {
                    $product_download_id = ProductDownload::getIdFromIdProduct($product->id);
                    $productDownload = new ProductDownload((bool) $product_download_id ? $product_download_id : null);

                    if (isset($data_item['nb_downloadable'])) {
                        $productDownload->nb_downloadable = $data_item['nb_downloadable'];
                    }

                    if (isset($data_item['nb_days_accessible'])) {
                        $productDownload->nb_days_accessible = $data_item['nb_days_accessible'];
                    }

                    if (isset($data_item['date_expiration']) && \Validate::isDate($data_item['date_expiration'])) {
                        $productDownload->date_expiration = $data_item['date_expiration'];
                    } else if (strtotime($productDownload->date_expiration) === strtotime('0000-00-00') ||
                        (bool) $product_download_id) {
                        $productDownload->date_expiration = '';
                    }

                    if (isset($data_item['file_url']) && \Validate::isUrl($data_item['file_url'])) {
                        $productDownload->filename = ProductDownload::getNewFilename();
                        \Tools::copy($data_item['file_url'], _PS_DOWNLOAD_DIR_ . $productDownload->filename);
                        $productDownload->display_filename = basename($data_item['file_url']);
                    }

                    if ((bool) $product_download_id) {
                        $productDownload->update();
                    } else {
                        $productDownload->id_product = (int) $product->id;
                        $productDownload->add();
                    }
                }
                /*
                 * else output error
                 */
            }

            if (isset($data_item['accessories'])) {
                if ($enumeration_str = self::getEnumerationString($data_item['accessories'])) {
                    $enumeration_arr = self::convertEnumerationStrToArray($enumeration_str);
                    $enumeration_arr = array_map(function ($enum) use ($product) {
                        foreach ($this->products as $p) {
                            if (($enum === $p['id_product'] || $enum == $p['name']) &&
                                ($this->shop_id === (int) $p['id_shop']) &&
                                $p['id_product'] !== $product->id) {

                                return (int) $p['id_product'];
                            }
                        }
                        return null;
                    }, $enumeration_arr);
                    $enumeration_arr = array_values(array_filter($enumeration_arr, function ($enum) {
                        return $enum !== null;
                    }));

                    $product->deleteAccessories();
                    $product->changeAccessories($enumeration_arr);
                }
            }

            if (isset($data_item['features'])) {
                if ($data_item['features']) {
                    $feature_str = self::getEnumerationString($data_item['features']);
                    $feature = self::convertEnumerationStrToArray($feature_str, ':');

                    $feature_name = isset($feature[0]) ? $feature[0] : '';
                    $feature_value = isset($feature[1]) ? $feature[1] : '';
                    $position = isset($feature[2]) ? (int) $feature[2] : false;
                    $custom = isset($feature[3]) ? (int) $feature[3] : false;

                    if (!empty($feature_name) && !empty($feature_value)) {
                        $id_feature = (int) Feature::addFeatureImport($feature_name, $position);
                        $id_feature_value = (int) FeatureValue::addFeatureValueImport($id_feature, $feature_value, $product->id, $this->language_id, $custom);
                        Product::addFeatureProductImport($product->id, $id_feature, $id_feature_value);
                        Feature::cleanPositions();
                    }
                }
            }

            if (isset($data_item['tags'])) {
                Tag::deleteTagsForProduct($product->id);
                Tag::addTags($this->language_id, $product->id, $data_item['tags']);
            }

            if (isset($data_item['delete_existing_images'])) {
                if ($data_item['delete_existing_images'] === '1' || true) {
                    $product->deleteImages();
                }
            }

            if (isset($data_item['image'])) {
                VarDumper::dump(Shop::getContext());
                $images_url = explode(',', $data_item['image']);
                $images_url = array_map(function($image_url) {
                    return trim($image_url);
                }, $images_url);

                $product_has_images = (bool) Image::getImages($this->language_id, (int) $product->id);
                foreach ($images_url as $key => $url) {
                    $url = trim($url);
                    $error = false;
                    if (!empty($url)) {
                        $url = str_replace(' ', '%20', $url);

                        $image = new Image();
                        $image->id_product = (int) $product->id;
                        $image->position = Image::getHighestPosition($product->id) + 1;
                        $image->cover = (!$key && !$product_has_images) ? true : false;
                        $alt = 'test alt';
                        if (strlen($alt) > 0) {
                            $image->legend = self::createMultiLangField($alt);
                        }
                        // file_exists doesn't work with HTTP protocol
                        if (($field_error = $image->validateFields(false, true)) === true &&
                            ($lang_field_error = $image->validateFieldsLang(false, true)) === true && $image->add()) {
                            // associate image to selected shops
//                            $image->associateTo($shops);
                            if (!self::copyImg($product->id, $image->id, $url, 'products', true)) {
                                $image->delete();
                                VarDumper::dump("Error copying image: $url");
                            }
                        } else {
                            $error = true;
                        }
                    } else {
                        $error = true;
                    }

                    if ($error) VarDumper::dump('error import img');
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

        if (isset($info['advanced_stock_management'])) {
            $product->advanced_stock_management = (int) $info['advanced_stock_management'];
        }

        if (isset($info['depends_on_stock'])) {
            $product->depends_on_stock = (int) $info['depends_on_stock'];
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

    public static function convertEnumerationStrToArray($str, $delimiter = ',')
    {
        $arr = array_map(function ($item) {
            return trim($item);
        }, explode($delimiter, $str));

        return $arr;
    }

    public function deleteAllProducts()
    {
        $save_shop_id = $this->context->shop->id;
        $save_shop_context = Shop::getContext();
        $shop_ids = Shop::getShops(false, null, true);

        foreach ($shop_ids as $shop_id) {
            Shop::setContext(Shop::CONTEXT_SHOP, (int)$shop_id);
            $this->context->shop->id = (int)$shop_id;
            $products = Product::getProducts($this->language_id, 0, 0, 'id_product', 'ASC', false, false, $this->context);

            foreach ($products as $product) {
                $productObject = new Product($product['id_product'], true);
                $productObject->delete();
            }
        }

        $this->context->shop->id = $save_shop_id;
        Shop::setContext($save_shop_context);
    }

    public function getProducts()
    {
        $save_shop_id = $this->context->shop->id;
        $save_shop_context = Shop::getContext();
        $shop_ids = Shop::getShops(false, null, true);

        $products = [];
        foreach ($shop_ids as $shop_id) {
            Shop::setContext(Shop::CONTEXT_SHOP, (int)$shop_id);
            $this->context->shop->id = (int)$shop_id;
            $products = array_merge($products, Product::getProducts($this->language_id, 0, 0, 'id_product', 'ASC', false, false, $this->context));
        }

        $this->context->shop->id = $save_shop_id;
        Shop::setContext($save_shop_context);
        return $products;
    }

    public static function createMultiLangField($field)
    {
        $res = [];
        foreach (Language::getIDs(false) as $id_lang) {
            $res[$id_lang] = $field;
        }

        return $res;
    }

    protected static function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true)
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', \Configuration::get('WATERMARK_TYPES'));

        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();

                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int) $id_entity;

                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int) $id_entity;

                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int) $id_entity;

                break;
            case 'stores':
                $path = _PS_STORE_IMG_DIR_ . (int) $id_entity;

                break;
        }

        $url = urldecode(trim($url));
        $parced_url = parse_url($url);

        if (isset($parced_url['path'])) {
            $uri = ltrim($parced_url['path'], '/');
            $parts = explode('/', $uri);
            foreach ($parts as &$part) {
                $part = rawurlencode($part);
            }
            unset($part);
            $parced_url['path'] = '/' . implode('/', $parts);
        }

        if (isset($parced_url['query'])) {
            $query_parts = array();
            parse_str($parced_url['query'], $query_parts);
            $parced_url['query'] = http_build_query($query_parts);
        }

        if (!function_exists('http_build_url')) {
            require_once _PS_TOOL_DIR_ . 'http_build_url/http_build_url.php';
        }

        $url = http_build_url('', $parced_url);

        $orig_tmpfile = $tmpfile;

        if (\Tools::copy($url, $tmpfile)) {
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
                @unlink($tmpfile);

                return false;
            }

            $tgt_width = $tgt_height = 0;
            $src_width = $src_height = 0;
            $error = 0;
            ImageManager::resize($tmpfile, $path . '.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5, $src_width, $src_height);
            $images_types = ImageType::getImagesTypes($entity, true);

            if ($regenerate) {
                $previous_path = null;
                $path_infos = array();
                $path_infos[] = array($tgt_width, $tgt_height, $path . '.jpg');
                foreach ($images_types as $image_type) {
                    $tmpfile = self::get_best_path($image_type['width'], $image_type['height'], $path_infos);

                    if (ImageManager::resize(
                        $tmpfile,
                        $path . '-' . stripslashes($image_type['name']) . '.jpg',
                        $image_type['width'],
                        $image_type['height'],
                        'jpg',
                        false,
                        $error,
                        $tgt_width,
                        $tgt_height,
                        5,
                        $src_width,
                        $src_height
                    )) {
                        // the last image should not be added in the candidate list if it's bigger than the original image
                        if ($tgt_width <= $src_width && $tgt_height <= $src_height) {
                            $path_infos[] = array($tgt_width, $tgt_height, $path . '-' . stripslashes($image_type['name']) . '.jpg');
                        }
                        if ($entity == 'products') {
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        \Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
        } else {
            @unlink($orig_tmpfile);

            return false;
        }
        unlink($orig_tmpfile);

        return true;
    }

    protected static function get_best_path($tgt_width, $tgt_height, $path_infos)
    {
        $path_infos = array_reverse($path_infos);
        $path = '';
        foreach ($path_infos as $path_info) {
            list($width, $height, $path) = $path_info;
            if ($width >= $tgt_width && $height >= $tgt_height) {
                return $path;
            }
        }
        return $path;
    }
}
