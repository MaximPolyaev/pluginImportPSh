<?php

use MaximCode\ImportPalmira\FileReader;
use MaximCode\ImportPalmira\ImportDB;
use MaximCode\ImportPalmira\ImportHelper;
use MaximCode\ImportPalmira\JsonCfg;
use MaximCode\ImportPalmira\ProgressManager;
use MaximCode\ImportPalmira\SessionHelper;
use MaximCode\ImportPalmira\TaskHelper;
use MaximCode\ImportPalmira\WebHelpers;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
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
                        WebHelpers::echoJson([]);
                    die;
                default:
                    WebHelpers::echoJson([
                        'response' => 'true',
                        'type_task' => 'empty'
                    ]);
                    die;
            }
        } catch (Exception $e) {
            WebHelpers::echoJson([
                'response' => 'true',
                'type_task' => 'empty',
                'errors' => ['get progress error: ' . $e->getMessage()]
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

    public function ajaxProcessImportOne()
    {
        set_time_limit(0);

        switch (Tools::getValue('type_task')) {
            case 'import_products':
                $this->importProducts();
                break;
            case 'delete_products':
                $this->deleteProducts();
                break;
            default:
                WebHelpers::echoJson([
                    'response' => 'true',
                    'type_task' => 'empty'
                ]);
                die;
        }
        die;
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
        $progress_num = Tools::getValue('progress_num') ? Tools::getValue('progress_num') : 0;
        $progress_num = $progress_num === 'none' ? 0 : $progress_num;

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
        $importDb = new ImportDB($this);
//        $products = $importDb->getProducts();

        $counter = $progress_num;
        $import_status = true;

        foreach ($import_data as $product_item) {
            $end_time = microtime(true) - $start_time;
            try {
                $importDb->send($product_item, $is_force_id);
            } catch (Exception $e) {
                $import_status = false;
                $manager->setProgressError("Import error. Product: " . implode('; ', array_map(function($field) {return htmlspecialchars($field);}, $product_item)) . "File: {$e->getFile()}. Line: {$e->getLine()}. {$e->getMessage()}");
            }

            $counter++;
            $manager->incrementProgress();
            $manager->incrementProgressImportNum($counter);
            $manager->setProgressMessage("Import Product: " . implode('; ', array_map(function($field) {return htmlspecialchars($field);}, $product_item)));

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
        $import_file_path = Tools::getValue('importpalmira_import_file_path');
        $import_matches = Tools::getValue('importpalmira_type_value');
        $is_force_id = (bool)Tools::getValue('importpalmira_force_id');

        $fileReader = (new FileReader($import_file_path))->init();
        $import_data = $fileReader->getData(0, 3) ?? 'error';
        if ($import_data === 'error') {
            WebHelpers::echoJson([
                'import_status' => false,
                'errors' => $fileReader->getErrors()
            ]);
            die;
        }

        $import_data = ImportHelper::optimize_matching($import_data, $import_matches);
        $importDb = new ImportDB($this);


        foreach ($import_data as $product_item) {
            $importDb->send($product_item);
        }

        VarDumper::dump($import_data);
        WebHelpers::echoJson([
            'import_status' => true
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
