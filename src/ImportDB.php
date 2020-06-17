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


use Configuration;
use Exception;
use Hook;
use Tools;
use Validate;
use PrestaShop\PrestaShop\Adapter\Entity\Address;
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
use PrestaShop\PrestaShop\Adapter\Entity\TaxManagerFactory;


class ImportDB
{
    /**
     * @var Context
     */
    private $context;
    private $module;
    /**
     * @var ProgressManager
     */
    private $progressManager;
    private $shop_ids;
    private $shop_id;
    private $currency_id;
    private $country_id;
    private $language_id;
    private $default_lang;
    private $default_category;
    private $products;
    private $errors = [];

    public function __construct($module, $progressManager)
    {
        $this->module = $module;
        $this->context = $this->module->getContext();
        $this->progressManager = $progressManager;
        $this->language_id = $this->context->language->id;
        $this->products = $this->getProducts();
        $this->default_lang = \Configuration::get('PS_LANG_DEFAULT') ?? 1;
        $this->default_category = \Configuration::get('PS_HOME_CATEGORY') ?? 1;
        $this->shop_id = $this->context->shop->id;
        $this->currency_id = $this->context->currency->id;
        $this->country_id = $this->context->country->id;

        $this->shop_ids = array_values(Shop::getShops(false, null, true));
        Shop::setContext(Shop::CONTEXT_SHOP, $this->shop_ids[0]);
    }

    public function send($import_product, $is_force_id = false)
    {
        $is_update = $this->isUpdate($import_product);

        if (isset($import_product['shop'])) {
            $shop_id = Shop::getIdByName($import_product['shop']);
            $import_product['shop'] = (bool)$shop_id ? $shop_id : Shop::getShop((int)$import_product['shop'])['id_shop'];
            unset($shop_id);

            if ((bool)$import_product['shop']) {
                Shop::setContext(Shop::CONTEXT_SHOP, (int)$import_product['shop']);
                $this->shop_id = (int)$import_product['shop'];
            }
        }

        $productObj = new Product($import_product['id'] ?? null, true);
        $productObj->id_category_default = $this->default_category;
//            $product->force_id = true;


        if (isset($import_product['id'])) {
            $productObj->id = $import_product['id'];
            $productObj->force_id = $is_force_id;
        }

        $this->addSimpleFields($productObj, $import_product);

        if (isset($import_product['price_tin']) && !isset($import_product['price_tax'])) {
            $id_tax_rules_group = 0;
            if ($import_product['id_tax_rules_group']) {
                $id_tax_rules_group = $import_product['id_tax_rules_group'];
            }

            $shop_id = $this->shop_ids[0];
            if ($import_product['shop']) {
                $shop_id = (int)$import_product['shop'];
            }

            $shop = new Shop($shop_id);
            $address = new Address();
            $address->company = $shop->name;
            $address->id_country = Configuration::get('PS_COUNTRY_DEFAULT');
            $taxManager = TaxManagerFactory::getManager($address, $id_tax_rules_group);
            $productTaxCalculator = $taxManager->getTaxCalculator();
            $total_rate = ($productTaxCalculator->getTotalRate() + 100) * 0.01;

            $productObj->price = (float)number_format((float)$import_product['price_tin'] / $total_rate, 6, '.', '');
        }

        if (isset($import_product['supplier_reference'])) {
            $productObj->addSupplierReference('1', 0, $import_product['supplier_reference'] . 'new', '10', '1');
            $productObj->supplier_reference = $import_product['supplier_reference'];
            $productObj->id_supplier = 1;
        }

        if (isset($import_product['manufacturer'])) {
            $productObj->id_manufacturer = Manufacturer::getIdByName($import_product['manufacturer']) ?? 0;
        }

        if (isset($import_product['ean13'])) {
            if (Validate::isEan13($import_product['ean13'])) {
                $productObj->ean13 = $import_product['ean13'];
            }
            /*
             * else output error
             */
        }

        if (isset($import_product['upc'])) {
            if (Validate::isUpc($import_product['upc'])) {
                $productObj->upc = $import_product['upc'];
            }
            /*
             * else output error
             */
        }

        if (isset($import_product['isbn'])) {
            if (Validate::isIsbn($import_product['isbn'])) {
                $productObj->isbn = $import_product['isbn'];
            }
            /*
             * else output error
             */
        }


        if ($is_update) {
            $productObj->update();
        } else {
            $productObj->add();
        }

        if (isset($import_product['category'])) {
            $new_categories = [$this->default_category];
            if ($enumeration_str = self::getEnumerationString($import_product['category'])) {
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

            } else if ($category = Category::searchByName($this->language_id, $import_product['category'], true)) {
                $new_categories[] = $category['id_category'];
                $new_categories = array_unique($new_categories);
            }
            $productObj->updateCategories($new_categories);
        } else if (!$is_update) {
            $new_categories = [$this->default_category];
            $productObj->updateCategories($new_categories);
        }

        if (isset($import_product['quantity'])) {
            StockAvailable::setQuantity($productObj->id, 0, $import_product['quantity'], $this->shop_id, false);
        }

        if (isset($import_product['out_of_stock'])) {
            StockAvailable::setProductOutOfStock($productObj->id, $import_product['out_of_stock'], $this->shop_id);
        }

        if (isset($import_product['reduction_percent']) || isset($import_product['reduction_price'])) {
            $this->addSpecificPrice($productObj->id, $import_product);
        }

        if (isset($import_product['nb_downloadable']) ||
            isset($import_product['nb_days_accessible']) ||
            isset($import_product['file_url']) ||
            (
                isset($import_product['date_expiration']) &&
                Validate::isDate($import_product['date_expiration'])
            )) {
            if ($productObj->is_virtual) {
                $product_download_id = ProductDownload::getIdFromIdProduct($productObj->id);
                $productDownload = new ProductDownload((bool)$product_download_id ? $product_download_id : null);

                if (isset($import_product['nb_downloadable'])) {
                    $productDownload->nb_downloadable = $import_product['nb_downloadable'];
                }

                if (isset($import_product['nb_days_accessible'])) {
                    $productDownload->nb_days_accessible = $import_product['nb_days_accessible'];
                }

                if (isset($import_product['date_expiration']) && Validate::isDate($import_product['date_expiration'])) {
                    $productDownload->date_expiration = $import_product['date_expiration'];
                } else if (strtotime($productDownload->date_expiration) === strtotime('0000-00-00') ||
                    (bool)$product_download_id) {
                    $productDownload->date_expiration = '';
                }

                if (isset($import_product['file_url']) && Validate::isUrl($import_product['file_url'])) {
                    $productDownload->filename = ProductDownload::getNewFilename();
                    Tools::copy($import_product['file_url'], _PS_DOWNLOAD_DIR_ . $productDownload->filename);
                    $productDownload->display_filename = basename($import_product['file_url']);
                }

                if ((bool)$product_download_id) {
                    $productDownload->update();
                } else {
                    $productDownload->id_product = (int)$productObj->id;
                    $productDownload->add();
                }
            }
            /*
             * else output error
             */
        }

        if (isset($import_product['accessories'])) {
            if ($enumeration_str = self::getEnumerationString($import_product['accessories'])) {
                $enumeration_arr = self::convertEnumerationStrToArray($enumeration_str);
                $enumeration_arr = array_map(function ($enum) use ($productObj) {
                    foreach ($this->products as $p) {
                        if (($enum === $p['id_product'] || $enum == $p['name']) &&
                            ($this->shop_id === (int)$p['id_shop']) &&
                            $p['id_product'] !== $productObj->id) {

                            return (int)$p['id_product'];
                        }
                    }
                    return null;
                }, $enumeration_arr);
                $enumeration_arr = array_values(array_filter($enumeration_arr, function ($enum) {
                    return $enum !== null;
                }));

                $productObj->deleteAccessories();
                $productObj->changeAccessories($enumeration_arr);
            }
        }

        if (isset($import_product['features'])) {
            if ($import_product['features']) {
                $feature_str = self::getEnumerationString($import_product['features']);
                $feature = self::convertEnumerationStrToArray($feature_str, ':');

                $feature_name = isset($feature[0]) ? $feature[0] : '';
                $feature_value = isset($feature[1]) ? $feature[1] : '';
                $position = isset($feature[2]) ? (int)$feature[2] : false;
                $custom = isset($feature[3]) ? (int)$feature[3] : false;

                if (!empty($feature_name) && !empty($feature_value)) {
                    $id_feature = (int)Feature::addFeatureImport($feature_name, $position);
                    $id_feature_value = (int)FeatureValue::addFeatureValueImport($id_feature, $feature_value, $productObj->id, $this->language_id, $custom);
                    Product::addFeatureProductImport($productObj->id, $id_feature, $id_feature_value);
                    Feature::cleanPositions();
                }
            }
        }

        if (isset($import_product['tags'])) {
            Tag::deleteTagsForProduct($productObj->id);
            Tag::addTags($this->language_id, $productObj->id, $import_product['tags']);
        }

        if (isset($import_product['delete_existing_images'])) {
            if ($import_product['delete_existing_images'] === '1' || true) {
                $productObj->deleteImages();
            }
        }

        if (isset($import_product['image'])) {
            $images_url = explode(',', $import_product['image']);
            $images_url = array_map(function ($image_url) {
                return trim($image_url);
            }, $images_url);

            $product_has_images = (bool)Image::getImages($this->language_id, (int)$productObj->id);
            foreach ($images_url as $key => $url) {
                $url = trim($url);
                $error = false;
                if (!empty($url)) {
                    $url = str_replace(' ', '%20', $url);

                    $image = new Image();
                    $image->id_product = (int)$productObj->id;
                    $image->position = Image::getHighestPosition($productObj->id) + 1;
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
                        if (!self::copyImg($productObj->id, $image->id, $url, 'products', true)) {
                            $image->delete();
                            $this->progressManager->setProgressError("Error copying image: $url");
                        }
                    } else {
                        $error = true;
                    }
                } else {
                    $error = true;
                }

                if ($error) $this->progressManager->setProgressError('Error import img');
            }
        }
    }

    /**
     * @param $productObj Product
     * @param $import_product
     */
    public function addSimpleFields($productObj, $import_product)
    {
        if (isset($import_product['active'])) {
            $productObj->active = $import_product['active'];
        }

        if (isset($import_product['name'])) {
            $productObj->name = $import_product['name'];
        }

        if (isset($import_product['price_tex'])) {
            $productObj->price = (float)$import_product['price_tex'];
        }

        if (isset($import_product['id_tax_rules_group'])) {
            $productObj->id_tax_rules_group = (int)$import_product['id_tax_rules_group'];
        }

        if (isset($import_product['wholesale_price'])) {
            $productObj->wholesale_price = $import_product['wholesale_price'];
        }

        if (isset($import_product['on_sale'])) {
            $productObj->on_sale = $import_product['on_sale'];
        }

        if (isset($import_product['reference'])) {
            $productObj->reference = $import_product['reference'];
        }

        if (isset($import_product['ecotax'])) {
            $productObj->ecotax = $import_product['ecotax'];
        }

        if (isset($import_product['width'])) {
            $productObj->width = $import_product['width'];
        }

        if (isset($import_product['height'])) {
            $productObj->height = $import_product['height'];
        }

        if (isset($import_product['depth'])) {
            $productObj->depth = $import_product['depth'];
        }

        if (isset($import_product['weight'])) {
            $productObj->weight = $import_product['weight'];
        }

        if (isset($import_product['delivery_in_stock'])) {
            $productObj->delivery_in_stock = $import_product['delivery_in_stock'];
        }

        if (isset($import_product['delivery_out_stock'])) {
            $productObj->delivery_out_stock = $import_product['delivery_out_stock'];
        }

        if (isset($import_product['minimal_quantity'])) {
            $productObj->minimal_quantity = $import_product['minimal_quantity'];
        }

        if (isset($import_product['low_stock_alert'])) {
            $productObj->low_stock_alert = $import_product['low_stock_alert'];
        }

        if (isset($import_product['low_stock_threshold'])) {
            $productObj->low_stock_threshold = $import_product['low_stock_threshold'] ? $import_product['low_stock_threshold'] : null;
        }

        if (isset($import_product['visibility'])) {
            if (in_array($import_product['visibility'], ['both', 'catalog', 'search', 'none'])) {
                $productObj->visibility = $import_product['visibility'];
            }
        }

        if (isset($import_product['additional_shipping_cost'])) {
            $productObj->additional_shipping_cost = $import_product['additional_shipping_cost'];
        }

        if (isset($import_product['unity'])) {
            $productObj->unity = $import_product['unity'] ? $import_product['unity'] : null;
        }

        if (isset($import_product['unit_price'])) {
            $productObj->unit_price = $import_product['unit_price'] ? $import_product['unit_price'] : null;
        }

        if (isset($import_product['description_short'])) {
            $productObj->description_short = $import_product['description_short'];
        }

        if (isset($import_product['description'])) {
            $productObj->description = $import_product['description'];
        }

        if (isset($import_product['meta_title'])) {
            $productObj->meta_title = $import_product['meta_title'];
        }

        if (isset($import_product['meta_description'])) {
            $productObj->meta_description = $import_product['meta_description'];
        }

        if (isset($import_product['meta_keywords'])) {
            $productObj->meta_keywords = $import_product['meta_keywords'];
        }

        if (isset($import_product['link_rewrite'])) {
            $productObj->link_rewrite = $import_product['link_rewrite'];
        }

        if (isset($import_product['available_now'])) {
            $productObj->available_now = $import_product['available_now'];
        }

        if (isset($import_product['available_later'])) {
            $productObj->available_later = $import_product['available_later'];
        }

        if (isset($import_product['available_for_order'])) {
            $productObj->available_for_order = $import_product['available_for_order'];
        }

        if (isset($import_product['available_date'])) {
            if (Validate::isDate($import_product['available_date'])) {
                $productObj->available_date = $import_product['available_date'];
            }
        }

        if (isset($import_product['date_add'])) {
            if (Validate::isDate($import_product['date_add'])) {
                $productObj->date_add = $import_product['date_add'];
            }
        }

        if (isset($import_product['show_price'])) {
            $productObj->show_price = $import_product['show_price'];
        }

        if (isset($import_product['online_only'])) {
            $productObj->online_only = $import_product['online_only'];
        }

        if (isset($import_product['condition'])) {
            if (in_array($import_product['condition'], ['new', 'used', 'refurbished'])) {
                $productObj->condition = $import_product['condition'];
            }
        }

        if (isset($import_product['customizable'])) {
            $productObj->customizable = $import_product['customizable'];
        }

        if (isset($import_product['uploadable_files'])) {
            $productObj->uploadable_files = $import_product['uploadable_files'];
        }

        if (isset($import_product['text_fields'])) {
            $productObj->text_fields = $import_product['text_fields'];
        }

        if (isset($import_product['is_virtual'])) {
            $productObj->is_virtual = $import_product['is_virtual'];
        }

        if (isset($import_product['advanced_stock_management'])) {
            $productObj->advanced_stock_management = (int)$import_product['advanced_stock_management'];
        }

        if (isset($import_product['depends_on_stock'])) {
            $productObj->depends_on_stock = (int)$import_product['depends_on_stock'];
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
            $specific_price->price = -1;
            $specific_price->id_customer = 0;
            $specific_price->from_quantity = 1;
            $specific_price->reduction_tax = 1;

            if (isset($info['reduction_percent']) && (bool)$info['reduction_percent']) {
                $specific_price->reduction = (int)$info['reduction_percent'] * 0.01;
                $specific_price->reduction_type = 'percentage';
            } else if ($info['reduction_price'] && (bool)$info['reduction_price']) {
                $specific_price->reduction = (int)$info['reduction_price'];
                $specific_price->reduction_type = 'amount';
            } else {
                $this->progressManager->setProgressError('Product id: ' . $product_id .  '. Error add specific price. Reduced interest or price reduction is not allowed');
                return;
            }

            if (isset($info['reduction_from']) && \Validate::isDate($info['reduction_from'])) {
                $specific_price->from = $info['reduction_from'];
            } else {
                $specific_price->from = '0000-00-00 00:00:00';
            }

            if (isset($info['reduction_to']) && \Validate::isDate($info['reduction_to'])) {
                $specific_price->to = $info['reduction_to'];
            } else {
                $specific_price->to = '0000-00-00 00:00:00';
            }

            if (!$specific_price->save()) {
                $this->progressManager->setProgressError('An error occurred while updating the specific price.');
                return;
            }
        } catch (Exception $e) {
            $this->progressManager->setProgressError('An error occurred while updating the specific price. ' . $e);
            return;
        }
    }

    public function isUpdate($import_product)
    {
        $is_update = false;
        if (isset($import_product['id'])) {
            foreach ($this->products as $product_item) {
                if ($product_item['id_product'] === $import_product['id']) {
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

    public function getProducts()
    {
        if (!empty($this->products)) {
            return $this->products;
        }

        $save_shop_id = $this->context->shop->id;
        $shop_ids = Shop::getShops(false, null, true);

        $products = [];
        foreach ($shop_ids as $shop_id) {
            Shop::setContext(Shop::CONTEXT_SHOP, (int)$shop_id);
            $this->context->shop->id = (int)$shop_id;
            $products = array_merge($products, Product::getProducts($this->language_id, 0, 0, 'id_product', 'ASC', false, false, $this->context));
        }

        $this->context->shop->id = $save_shop_id;
        $shops_ids = array_values(Shop::getShops(false, null, true));
        Shop::setContext(Shop::CONTEXT_SHOP, $shops_ids[0]);
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
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));

        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();

                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int)$id_entity;

                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int)$id_entity;

                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int)$id_entity;

                break;
            case 'stores':
                $path = _PS_STORE_IMG_DIR_ . (int)$id_entity;

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

        if (Tools::copy($url, $tmpfile)) {
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
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$id_entity . '_' . (int)Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
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

    public function __destruct()
    {
        Tools::clearCache();
    }
}
