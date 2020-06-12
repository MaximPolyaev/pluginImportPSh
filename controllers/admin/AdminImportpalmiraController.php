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
        $progress = ProgressManager::getProgress();
        $count_products = count(Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'DESC', false, false, $this->context));
        if ($progress !== null)
            WebHelpers::echoJson([
                'progress' => $progress,
                'session' => isset($_SESSION) ? $_SESSION : null,
                'remaining_progress_num' => $count_products
            ]);
        else
            WebHelpers::echoJson([]);

        exit;
    }

    public function ajaxProcessProgressNew()
    {
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

        WebHelpers::echoJson([
            'task' => TaskHelper::generateTaskId(),
            'full_progress_count' => $full_progress_count
        ]);
        exit;
    }

    public function ajaxProcessLongProgress()
    {
        set_time_limit(0);
        $start_time = microtime(true);

        $task_id = TaskHelper::getTaskId();
        if ($task_id === null) {
            return;
        }

        $manager = new ProgressManager($task_id);
        $products = Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'DESC', false, false, $this->context);
        $manager->setStepCount(count($products));

        foreach ($products as $product) {
            if (isset($product['id_product'])) {
                $productObject = new Product($product['id_product'], true);
                $productObject->delete();

                $manager->incrementProgress();

                $end_time = microtime(true) - $start_time;
                if ($end_time > 10) {
                    WebHelpers::echoJson([
                        'endlongprogress' => true,
                        'endtime' => $end_time,
                        'status_progress' => 'next',
                        'current_progress' => SessionHelper::get('progress' . $task_id),
                        'session' => $_SESSION
                    ]);
                    exit;
                }
            }
        }

        WebHelpers::echoJson([
            'endlongprogress' => true,
            'endtime' => microtime(true) - $start_time,
            'status_progress' => 'end'
        ]);
        exit;
    }

    public function ajaxProcessTestAjax()
    {
        set_time_limit(0);
        $import_file_path = Tools::getValue('importpalmira_import_file_path');

        $fileReader = (new FileReader($import_file_path))->init();

        $import_matches = Tools::getValue('importpalmira_type_value');
        $import_headers = $fileReader->getHeaders() ?? 'error';
        $import_data = $fileReader->getData(0, 400) ?? 'error';

        if ($import_headers === 'error' || $import_data === 'error') {
            WebHelpers::echoJson([
                'response' => 'true',
                'import_status' => false,
                'errors' => $fileReader->getErrors()
            ]);
            die;
        }

        $import_data = ImportHelper::optimize_matching($import_data, $import_matches);

        VarDumper::dump($import_data);
        $importDb = new ImportDB($this, $import_data);

        WebHelpers::echoJson(['response' => 'true', 'import_status' => true]);
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

    public function getContext() {
        return $this->context;
    }
}
