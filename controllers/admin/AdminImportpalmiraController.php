<?php

use MaximCode\ImportPalmira\FileReader;
use MaximCode\ImportPalmira\JsonCfg;
use MaximCode\ImportPalmira\ProgressManager;
use MaximCode\ImportPalmira\TaskHelper;
use MaximCode\ImportPalmira\WebHelpers;
use Symfony\Component\VarDumper\VarDumper;


class AdminImportpalmiraController extends ModuleAdminController
{
    const STEP_COUNT = 60;
    const STEP_DELAY = 200000;

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
        if ($progress !== null)
            WebHelpers::echoJson(['progress' => $progress]);
        else
            WebHelpers::echoJson([]);

        exit;
    }

    public function ajaxProcessProgressNew()
    {
        WebHelpers::echoJson(['task' => TaskHelper::generateTaskId()]);

        exit;
    }

    public function ajaxProcessLongProgress()
    {
        $task_id = TaskHelper::getTaskId();
        if ($task_id === null)
            return;

        set_time_limit(0);

        $manager = new ProgressManager($task_id);
        $manager->setStepCount(self::STEP_COUNT);

        for ($i = 0; $i !== self::STEP_COUNT; ++$i) {
            $manager->incrementProgress();
            usleep(self::STEP_DELAY);
        }

        WebHelpers::echoJson(['endlongprogress' => true]);
        exit;
    }

    public function ajaxProcessTestAjax()
    {
        $json = ['ajaxProcessTestAjax' => 'data'];

        $json['import_file_path'] = Tools::getValue('importpalmira_import_file_path');

        $fileReader = (new FileReader($json['import_file_path']))->init();

        $json['read_headers'] = $fileReader->getHeaders() ?? 'error';
        $json['read_data'] = $fileReader->getData() ?? 'error';


        $json['errors'] = $fileReader->getErrors();
        WebHelpers::echoJson($json);
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
}
