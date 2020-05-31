<?php

use MaximCode\ImportPalmira\ProgressManager;
use MaximCode\ImportPalmira\TaskHelper;
use MaximCode\ImportPalmira\WebHelpers;

class AdminImportpalmiraController extends ModuleAdminController
{
    const STEP_COUNT = 5000;
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
}
