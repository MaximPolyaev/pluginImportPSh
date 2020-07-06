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

use MaximCode\ImportPalmira\FileReader;
use MaximCode\ImportPalmira\ImportDB;
use MaximCode\ImportPalmira\ImportHelper;
use MaximCode\ImportPalmira\JsonCfg;
use MaximCode\ImportPalmira\ProgressManager;
use MaximCode\ImportPalmira\SessionHelper;
use MaximCode\ImportPalmira\TaskHelper;
use MaximCode\ImportPalmira\WebHelpers;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\Shop;
use Symfony\Component\VarDumper\VarDumper;


class AdminImportpalmiraController extends ModuleAdminController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function ajaxProcessGetProgress()
    {
        try {
            switch (Tools::getValue('type_task')) {
                case 'import_products':
                    $progress = ProgressManager::getProgress();
                    $count_progress = ProgressManager::getImportProgressNum();

                    WebHelpers::echoJson([
                        'progress' => $progress,
                        'session' => isset($_SESSION) ? $_SESSION : null,
                        'progress_num' => $count_progress,
                        'messages' => ProgressManager::getProgressMessages(true),
                        'errors' => ProgressManager::getProgressErrors(true)
                    ]);
                    die;
                case 'delete_all_products':
                    $progress = ProgressManager::getProgress();
                    $count_products = count(Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'DESC', false, false, $this->context));
                    if ($progress !== null)
                        WebHelpers::echoJson([
                            'progress' => $progress,
                            'session' => isset($_SESSION) ? $_SESSION : null,
                            'remaining_progress_num' => $count_products,
                            'messages' => ProgressManager::getProgressMessages(true),
                            'errors' => ProgressManager::getProgressErrors(true)
                        ]);
                    else
                        WebHelpers::echoJson([
                            'response' => 'true',
                            'type_task' => 'empty',
                            'progress' => 0
                        ]);
                    die;
                default:
                    WebHelpers::echoJson([
                        'response' => 'true',
                        'type_task' => 'empty',
                        'progress' => '0'
                    ]);
                    die;
            }
        } catch (Exception $e) {
            WebHelpers::echoJson([
                'response' => 'true',
                'type_task' => 'empty',
                'errors' => ['get progress error: ' . $e->getMessage()],
                'progress' => '0'
            ]);
            die;
        }
    }

    public function ajaxProcessProgressNew()
    {
        $full_progress_count = 0;
        switch (Tools::getValue('type_task')) {
            case 'delete_all_products':
                $products = Product::getProducts(
                    $this->context->language->id,
                    0,
                    0,
                    'id_product',
                    'DESC',
                    false,
                    false,
                    $this->context
                );

                $full_progress_count = count($products);
                break;
            case 'import_products':
                $import_file_path = Tools::getValue('importpalmira_import_file_path');
                $num_skip_rows = Tools::getValue('importpalmira_num_skip_rows');

                $fileReader = (new FileReader($import_file_path))->init();
                $import_data = $fileReader->getData(0, 0, $num_skip_rows) ?? 'error';
                if (empty($import_data) || $import_data === 'error') {
                    WebHelpers::echoJson([
                        'response' => 'true',
                        'type_task' => 'empty'
                    ]);
                    die;
                }
                $full_progress_count = count($import_data !== 'error' ? $import_data : []);
                break;
            default:
                WebHelpers::echoJson([
                    'response' => 'true',
                    'type_task' => 'empty'
                ]);
                die;
        }

        WebHelpers::echoJson([
            'task' => TaskHelper::generateTaskId(),
            'full_progress_count' => $full_progress_count
        ]);
        exit;
    }

    public function ajaxProcessLongProgress()
    {
        set_time_limit(0);

        switch (Tools::getValue('type_task')) {
            case 'import_products':
                $this->importProducts();
                die;
            case 'delete_all_products':
                $this->deleteProducts();
                die;
            default:
                WebHelpers::echoJson([
                    'response' => 'true',
                    'type_task' => 'empty'
                ]);
                die;
        }
    }

    private function importProducts()
    {
        $start_time = microtime(true);

        $task_id = TaskHelper::getTaskId();
        if ($task_id === null) {
            return;
        }

        $import_file_path = Tools::getValue('importpalmira_import_file_path');
        $num_skip_rows = Tools::getValue('importpalmira_num_skip_rows');
        $import_matches = Tools::getValue('importpalmira_type_value');
        $is_force_id = (bool)Tools::getValue('importpalmira_force_id');
        $only_update = (bool)Tools::getValue('importpalmira_only_update');
        $progress_num = Tools::getValue('progress_num') ? Tools::getValue('progress_num') : 0;
        $progress_num = $progress_num === 'none' ? 0 : $progress_num;

        $unique_field = Tools::getValue('importpalmira_reference_key');
        if ($unique_field === 'no') {
            $unique_field = false;
        }

        $fileReader = (new FileReader($import_file_path))->init();
        $import_data = $fileReader->getData($progress_num, 0, $num_skip_rows) ?? 'error';
        if ($import_data === 'error') {
            WebHelpers::echoJson([
                'response' => 'true',
                'import_status' => false,
                'status_progress' => 'end',
                'messages' => ProgressManager::getProgressMessages(true),
                'errors' => array_merge(
                    $fileReader->getErrors(),
                    ProgressManager::getProgressErrors(true)
                )
            ]);
            die;
        }

        $manager = new ProgressManager($task_id);
        $manager->setStepCount(count($import_data !== 'error' ? $import_data : []));

        $import_data = ImportHelper::optimize_matching($import_data, $import_matches);
        $importDb = new ImportDB($this, $manager);

        $counter = $progress_num;
        $import_status = true;

        foreach ($import_data as $product_item) {
            $end_time = microtime(true) - $start_time;

            $is_import_product = false;
            if (!$is_force_id && !$only_update && !(bool)$unique_field) {
                $is_import_product = true;
            } else if ($is_force_id && !$only_update && !(bool)$unique_field) {
                if (isset($product_item['id'])) {
                    if ((int)$product_item['id'] === 0) {
                        $manager->setProgressError(
                            "Error import: product ID 0 or '' cannot exist. Import Product: " .
                            implode(
                                '; ',
                                array_map(function ($field) {
                                    return htmlspecialchars($field);
                                }, $product_item)
                            )
                        );
                    } else {
                        $is_import_product = true;
                    }
                } else {
                    $manager->setProgressError(
                        "Error import: no ID information. Import Product: " .
                        implode(
                            '; ',
                            array_map(function ($field) {
                                return htmlspecialchars($field);
                            }, $product_item)
                        )
                    );
                }
            } else if ($only_update && !(bool)$unique_field) {
                $manager->setProgressError("Only update does not work with no unique key");
                WebHelpers::echoJson([
                    'response' => 'true',
                    'import_status' => $import_status,
                    'progress_num' => $counter,
                    'status_progress' => 'end',
                    'messages' => ProgressManager::getProgressMessages(true),
                    'errors' => ProgressManager::getProgressErrors(true)
                ]);
                exit;
            } else if (!$only_update && $unique_field === 'id') { // Force id maybe true and false
                if (isset($product_item['id'])) {
                    if ((int)$product_item['id'] === 0) {
                        $manager->setProgressError(
                            "Error import: product ID 0 or '' cannot exist. Import Product: " .
                            implode(
                                '; ',
                                array_map(function ($field) {
                                    return htmlspecialchars($field);
                                }, $product_item)
                            )
                        );
                    } else if (ImportHelper::isExistProductByField('id', $product_item['id'])) {
                        $manager->setProgressError(
                            "Error import: product ID ${product_item['id']} already exists. Import Product: " .
                            implode(
                                '; ',
                                array_map(function ($field) {
                                    return htmlspecialchars($field);
                                }, $product_item)
                            )
                        );
                    } else {
                        $is_import_product = true;
                    }
                } else {
                    $manager->setProgressError(
                        "Error import: no ID information. Import Product: " .
                        implode(
                            '; ',
                            array_map(function ($field) {
                                return htmlspecialchars($field);
                            }, $product_item)
                        )
                    );
                }
            } else if ($is_force_id && $only_update && $unique_field === 'id') {
                if (isset($product_item['id'])) {
                    if ((int)$product_item['id'] === 0) {
                        $manager->setProgressError(
                            "Error import: product ID 0 or '' cannot exist. Import Product: " .
                            implode(
                                '; ',
                                array_map(function ($field) {
                                    return htmlspecialchars($field);
                                }, $product_item)
                            )
                        );
                    } else if (ImportHelper::isExistProductByField('id', $product_item['id'])) {
                        $is_import_product = true;
                    } else {
                        $manager->setProgressError(
                            "Error import: product ID ${product_item['id']} does not exist. Import Product: " .
                            implode(
                                '; ',
                                array_map(function ($field) {
                                    return htmlspecialchars($field);
                                }, $product_item)
                            )
                        );
                    }
                } else {
                    $manager->setProgressError(
                        "Error import: no ID information. Import Product: " .
                        implode(
                            '; ',
                            array_map(function ($field) {
                                return htmlspecialchars($field);
                            }, $product_item)
                        )
                    );
                }
            } else if (!$is_force_id && !$only_update && (bool)$unique_field) {
                if (isset($product_item[$unique_field])) {
                    if (ImportHelper::isExistProductByField(
                        $unique_field,
                        $product_item[$unique_field],
                        $this->context)) {
                        $manager->setProgressError(
                            "Error import: product $unique_field ${product_item[$unique_field]} already exists. Import Product: " .
                            implode(
                                '; ',
                                array_map(function ($field) {
                                    return htmlspecialchars($field);
                                }, $product_item)
                            )
                        );
                    } else {
                        $is_import_product = true;
                    }
                } else {
                    $manager->setProgressError(
                        "Error import: no $unique_field information. Import Product: " .
                        implode(
                            '; ',
                            array_map(function ($field) {
                                return htmlspecialchars($field);
                            }, $product_item)
                        )
                    );
                }
            } else if ($is_force_id && !$only_update && (bool)$unique_field) {
                if (isset($product_item[$unique_field])) {
                    if (ImportHelper::isExistProductByField(
                        $unique_field,
                        $product_item[$unique_field],
                        $this->context)) {
                        $is_import_product = true;
                        $product_item['id'] = ImportHelper::getProductIdByField(
                            $unique_field,
                            $product_item[$unique_field],
                            $this->context
                        );
                    } else {
                        $is_import_product = true;
                        $product_item['id'] = 0;
                    }
                } else {
                    $manager->setProgressError(
                        "Error import: no $unique_field information. Import Product: " .
                        implode(
                            '; ',
                            array_map(function ($field) {
                                return htmlspecialchars($field);
                            }, $product_item)
                        )
                    );
                }
            } else if ($is_force_id && $only_update && (bool)$unique_field) {
                if (isset($product_item[$unique_field])) {
                    if (ImportHelper::isExistProductByField(
                        $unique_field,
                        $product_item[$unique_field],
                        $this->context)) {
                        $is_import_product = true;
                        $product_item['id'] = ImportHelper::getProductIdByField(
                            $unique_field,
                            $product_item[$unique_field],
                            $this->context
                        );
                    } else {
                        $manager->setProgressError(
                            "Error import: product $unique_field ${product_item[$unique_field]} does not exists. Import Product: " .
                            implode('; ', array_map(function ($field) {
                                    return htmlspecialchars($field);
                                }, $product_item)
                            )
                        );
                    }
                } else {
                    $manager->setProgressError(
                        "Error import: no $unique_field information. Import Product: " .
                        implode(
                            '; ',
                            array_map(function ($field) {
                                return htmlspecialchars($field);
                            }, $product_item)
                        )
                    );
                }
            } else if ((bool)$unique_field && $only_update && !$is_force_id) {
                $manager->setProgressError(
                    "It is not possible to update goods, because Force ID is not in mode"
                );
                WebHelpers::echoJson([
                    'response' => 'true',
                    'import_status' => $import_status,
                    'progress_num' => $counter,
                    'status_progress' => 'end',
                    'messages' => ProgressManager::getProgressMessages(true),
                    'errors' => ProgressManager::getProgressErrors(true)
                ]);
                exit;
            }

            if ($is_import_product) try {
                $importDb->send($product_item, $is_force_id);
                $manager->setProgressMessage("Import Product: " . implode('; ', array_map(function ($field) {
                        return htmlspecialchars($field);
                    }, $product_item)));
            } catch (Exception $e) {
                $import_status = false;
                $manager->setProgressError("Import error. Product: " . implode('; ', array_map(function ($field) {
                        return htmlspecialchars($field);
                    }, $product_item)) . "File: {$e->getFile()}. Line: {$e->getLine()}. {$e->getMessage()}");
            }

            $counter++;
            $manager->incrementProgress();
            $manager->incrementProgressImportNum($counter);

            if ($end_time > 10) {
                WebHelpers::echoJson([
                    'endlongprogress' => true,
                    'endtime' => $end_time,
                    'status_progress' => 'next',
                    'current_progress' => SessionHelper::get('progress' . $task_id),
                    'progress_num' => $counter,
                    'session' => $_SESSION,
                    'messages' => ProgressManager::getProgressMessages(true),
                    'errors' => ProgressManager::getProgressErrors(true)
                ]);
                exit;
            }
        }

        WebHelpers::echoJson([
            'response' => 'true',
            'import_status' => $import_status,
            'progress_num' => $counter,
            'status_progress' => 'end',
            'messages' => ProgressManager::getProgressMessages(true),
            'errors' => ProgressManager::getProgressErrors(true)
        ]);
        die;
    }

    private function deleteProducts()
    {
        $start_time = microtime(true);

        $task_id = TaskHelper::getTaskId();
        if ($task_id === null) {
            return;
        }

        $shop_ids = Shop::getShops(false, null, true);
        $products = [];
        foreach ($shop_ids as $shop_id) {
            Shop::setContext(Shop::CONTEXT_SHOP, (int)$shop_id);
            $this->context->shop->id = (int)$shop_id;

            $products = array_merge(
                $products,
                Product::getProducts(
                    $this->context->language->id,
                    0,
                    0,
                    'id_product',
                    'DESC',
                    false,
                    false,
                    $this->context
                )
            );
        }
        $manager = new ProgressManager($task_id);
        $manager->setStepCount(count($products));

        foreach ($products as $product) {
            if (isset($product['id_product'])) {
                Shop::setContext(Shop::CONTEXT_SHOP, (int)$product['id_shop']);
                $this->context->shop->id = (int)$product['id_shop'];
                $productObject = new Product($product['id_product'], true);
                $productObject->delete();

                $manager->setProgressMessage("Delete product. Name: {$product['name']}. ID: {$product['id_product']}");

                $manager->incrementProgress();

                $end_time = microtime(true) - $start_time;
                if ($end_time > 10) {
                    WebHelpers::echoJson([
                        'endlongprogress' => true,
                        'endtime' => $end_time,
                        'status_progress' => 'next',
                        'current_progress' => SessionHelper::get('progress' . $task_id),
                        'session' => $_SESSION,
                        'messages' => ProgressManager::getProgressMessages(true),
                        'errors' => ProgressManager::getProgressErrors(true)
                    ]);
                    exit;
                }
            }
        }

        WebHelpers::echoJson([
            'endlongprogress' => true,
            'endtime' => microtime(true) - $start_time,
            'status_progress' => 'end',
            'messages' => ProgressManager::getProgressMessages(true),
            'errors' => ProgressManager::getProgressErrors(true)
        ]);
        die;
    }

    public function ajaxProcessTestAjax()
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
            $products = array_merge($products, Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'ASC', false, false, $this->context));
        }

        $this->context->shop->id = $save_shop_id;
        $shops_ids = array_values(Shop::getShops(false, null, true));
        Shop::setContext(Shop::CONTEXT_SHOP, $shops_ids[0]);

        VarDumper::dump($products);

        die;
    }

    public function ajaxProcessImportOne()
    {
        WebHelpers::echoJson([
            'response' => 'true',
            'type_task' => 'empty'
        ]);
        die;
    }

    public function ajaxProcessSaveJsonCfg()
    {
        $jsonCfg = new JsonCfg();
        $jsonCfg->save(Tools::getValue('name_cfg'), Tools::getValue('new_json_data'));

        WebHelpers::echoJson(['save_json' => $jsonCfg->getSaveStatus(), 'save_json_errors' => $jsonCfg->getSaveErrors()]);
        die;
    }

    public function ajaxProcessDeleteJsonCfg()
    {
        $jsonCfg = new JsonCfg();
        $jsonCfg->delete(Tools::getValue('delete_name_cfg'));

        WebHelpers::echoJson(['delete_json' => $jsonCfg->getDeleteStatus(), 'delete_json_errors' => $jsonCfg->getDeleteErrors()]);
        die;
    }

    public function ajaxProcessUseJsonCfg()
    {
        $jsonCfg = new JsonCfg();

        WebHelpers::echoJson([
            'json_cfg_data' => $jsonCfg->getData(Tools::getValue('name_cfg')),
            'use_json_errors' => $jsonCfg->getUseErrors()
        ]);
        die;
    }

    public function getContext()
    {
        return $this->context;
    }
}
